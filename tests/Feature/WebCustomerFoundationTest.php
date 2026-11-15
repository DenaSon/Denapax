<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Customer;
use App\Models\CustomerLoginCode;
use App\Models\Product;
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

    private function variant(): \App\Models\ProductVariant
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
