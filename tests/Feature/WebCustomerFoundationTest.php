<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLoginCode;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\CustomerOtpSender;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class WebCustomerFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_guest_cart_survives_mobile_verification_and_is_merged_into_customer_cart(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 2])
            ->assertRedirect(route('cart.index'));
        $this->get(route('cart.index'))->assertOk()->assertSee('سوهان دنا')->assertSee('2');

        $code = '';
        $sender = Mockery::mock(CustomerOtpSender::class);
        $sender->shouldReceive('send')->once()->withArgs(function (string $mobile, string $otp) use (&$code): bool {
            $code = $otp;

            return $mobile === '09120000011';
        });
        $this->app->instance(CustomerOtpSender::class, $sender);

        $this->post(route('customer.otp.request'), ['mobile' => '۰۹۱۲۰۰۰۰۰۱۱'])
            ->assertRedirect()->assertSessionHas('customer_otp_mobile', '09120000011');

        $this->post(route('customer.otp.verify'), ['code' => $code])
            ->assertRedirect(route('cart.index'));

        $this->assertAuthenticated('customer');
        $customer = Customer::query()->sole();
        $this->assertSame('09120000011', $customer->mobile);
        $this->assertNotNull($customer->mobile_verified_at);
        $this->assertSame(2, $customer->cart->items()->sole()->quantity);
        $this->assertDatabaseCount('customer_login_codes', 0);

        $this->post(route('customer.logout'))->assertRedirect(route('home'));
        $this->assertGuest('customer');
    }

    public function test_persian_otp_digits_are_accepted_and_checkout_intent_survives_login(): void
    {
        $variant = $this->variant();
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1]);
        $this->get(route('checkout.index'))->assertRedirect(route('customer.login'));

        CustomerLoginCode::create([
            'mobile' => '09120000015',
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        $this->withSession(['customer_otp_mobile' => '09120000015']);
        $this->get(route('customer.login'))->assertOk()->assertSee('تأیید شماره موبایل');
        $this->post(route('customer.otp.verify'), ['code' => '۱۲۳۴۵۶'])
            ->assertRedirect(route('checkout.index'));

        $this->assertAuthenticated('customer');
        $this->assertSame(1, Customer::query()->sole()->cart->items()->sole()->quantity);
    }

    public function test_otp_screen_shows_server_timed_expiry_and_resend_feedback(): void
    {
        $this->travelTo(now()->startOfSecond());

        $sentMobiles = [];
        $sender = Mockery::mock(CustomerOtpSender::class);
        $sender->shouldReceive('send')
            ->twice()
            ->withArgs(function (string $mobile, string $code) use (&$sentMobiles): bool {
                $sentMobiles[] = $mobile;

                return in_array($mobile, ['09120000016', '09120000017'], true)
                    && preg_match('/^[0-9]{6}$/D', $code) === 1;
            });
        $this->app->instance(CustomerOtpSender::class, $sender);

        $this->from(route('customer.login'))
            ->post(route('customer.otp.request'), ['mobile' => '09120000016'])
            ->assertRedirect(route('customer.login'))
            ->assertSessionHas('customer_otp_mobile', '09120000016');

        $expiresAt = (now()->addMinutes(5)->timestamp) * 1000;
        $resendAt = (now()->addSeconds(60)->timestamp) * 1000;

        $this->get(route('customer.login'))->assertOk()
            ->assertSee('data-otp-current-mobile="09120000016"', false)
            ->assertSee('data-otp-server-now="'.(now()->timestamp * 1000).'"', false)
            ->assertSee('data-otp-expires-at="'.$expiresAt.'"', false)
            ->assertSee('data-otp-resend-at="'.$resendAt.'"', false)
            ->assertSee('data-otp-code-usable="1"', false)
            ->assertSee('x-data="otpChallenge"', false)
            ->assertSee('x-bind:disabled="requestDisabled"', false)
            ->assertSee('x-text="expiryMessage"', false)
            ->assertSee('x-text="resendMessage"', false)
            ->assertSee('ارسال مجدد', false);

        // The server remains authoritative even when the client countdown is
        // used to prevent an early repeat request for the same mobile.
        $this->travel(30)->seconds();
        $this->from(route('customer.login'))
            ->post(route('customer.otp.request'), ['mobile' => '09120000016'])
            ->assertRedirect(route('customer.login'))
            ->assertSessionHasErrors('mobile');

        $this->get(route('customer.login'))->assertOk()
            ->assertSee('data-otp-resend-at="'.$resendAt.'"', false);

        // Changing the destination number is not blocked by the previous
        // number's 60-second cooldown; the IP rate limit still applies.
        $this->from(route('customer.login'))
            ->post(route('customer.otp.request'), ['mobile' => '09120000017'])
            ->assertRedirect(route('customer.login'))
            ->assertSessionHas('customer_otp_mobile', '09120000017');

        $this->assertSame(['09120000016', '09120000017'], $sentMobiles);
    }

    public function test_otp_screen_marks_expired_or_exhausted_challenges_unusable(): void
    {
        CustomerLoginCode::query()->create([
            'mobile' => '09120000018',
            'code_hash' => Hash::make('123456'),
            'attempts' => 5,
            'expires_at' => now()->subMinute(),
            'last_sent_at' => now()->subMinutes(2),
        ]);

        $this->withSession(['customer_otp_mobile' => '09120000018'])
            ->get(route('customer.login'))
            ->assertOk()
            ->assertSee('data-otp-code-usable="0"', false)
            ->assertSee('کد فعلی منقضی یا قابل استفاده نیست؛ کد تازه دریافت کنید.');
    }

    public function test_invalid_otp_attempts_are_persisted_and_block_replay(): void
    {
        CustomerLoginCode::query()->create([
            'mobile' => '09120000012',
            'code_hash' => Hash::make('123456'),
            'attempts' => 0,
            'expires_at' => now()->addMinutes(5),
            'last_sent_at' => now(),
        ]);

        $this->withSession(['customer_otp_mobile' => '09120000012']);
        for ($i = 0; $i < 5; $i++) {
            $this->post(route('customer.otp.verify'), ['code' => '654321'])->assertSessionHasErrors('code');
        }

        $this->assertSame(5, CustomerLoginCode::query()->sole()->attempts);
        $this->post(route('customer.otp.verify'), ['code' => '123456'])->assertSessionHasErrors('code');
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_missing_sms_configuration_fails_closed_and_unavailable_items_cannot_enter_cart(): void
    {
        config()->set('customer_auth.sms_driver', 'disabled');
        $this->post(route('customer.otp.request'), ['mobile' => '09120000013'])
            ->assertSessionHasErrors('mobile');
        $this->assertDatabaseCount('customer_login_codes', 0);

        $variant = $this->variant();
        $variant->update(['is_available' => false]);
        $this->post(route('cart.add'), ['variant_id' => $variant->id, 'quantity' => 1])
            ->assertSessionHasErrors('cart');
    }

    public function test_customer_session_does_not_grant_staff_admin_access(): void
    {
        $customer = Customer::query()->create(['mobile' => '09120000014', 'mobile_verified_at' => now()]);
        $this->actingAs($customer, 'customer')
            ->get('/admin')->assertRedirect('/login');
    }

    private function variant(): ProductVariant
    {
        $category = Category::query()->create([
            'name' => 'سوغات', 'slug' => 'gifts', 'is_active' => true,
        ]);
        $product = Product::query()->create([
            'category_id' => $category->id,
            'title' => 'سوهان دنا',
            'slug' => 'dena-sohan',
            'summary' => 'سوغات دنا',
            'is_active' => true,
        ]);

        return $product->variants()->create([
            'sku' => 'SOHAN-1',
            'title' => 'بسته ۵۰۰ گرمی',
            'sale_quantity' => 500,
            'sale_unit' => 'g',
            'price_irr' => 2500000,
            'shipping_weight_grams' => 600,
            'is_available' => true,
        ]);
    }
}
