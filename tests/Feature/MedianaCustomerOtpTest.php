<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerLoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class MedianaCustomerOtpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        config()->set('customer_auth.sms_driver', 'mediana');
        config()->set('customer_auth.otp_length', 4);
        config()->set('customer_auth.otp_ttl_seconds', 120);
        config()->set('customer_auth.otp_max_attempts', 3);
        config()->set('services.mediana', [
            'api_key' => 'test-only-key',
            'otp_pattern_code' => '110022',
            'timeout' => 8,
            'connect_timeout' => 3,
        ]);
    }

    public function test_mediana_sends_four_digit_otp_and_customer_can_log_in(): void
    {
        Http::fake(['api.mediana.ir/*' => Http::response($this->acceptedResponse())]);

        $this->post(route('customer.otp.request'), ['mobile' => '۰۹۱۲۰۰۰۰۰۹۱'])
            ->assertRedirect()
            ->assertSessionHas('customer_otp_mobile', '09120000091');

        $code = null;
        Http::assertSent(function (Request $request) use (&$code): bool {
            $code = $request['otpCode'];

            return $request->method() === 'POST'
                && $request->url() === 'https://api.mediana.ir/sms/v1/send/otp'
                && $request->hasHeader('X-API-KEY', 'test-only-key')
                && $request['patternCode'] === '110022'
                && $request['recipient'] === '09120000091'
                && is_string($code)
                && preg_match('/^[0-9]{4}$/D', $code) === 1;
        });
        Http::assertSentCount(1);

        $challenge = CustomerLoginCode::query()->sole();
        $this->assertIsString($code);
        $this->assertTrue(Hash::check($code, $challenge->code_hash));
        $this->assertNotSame($code, $challenge->code_hash);
        $this->assertSame(now()->addSeconds(120)->timestamp, $challenge->expires_at->timestamp);

        $this->get(route('customer.login'))->assertOk()
            ->assertSee('maxlength="4"', false)
            ->assertSee('کد چهاررقمی');

        $this->post(route('customer.otp.verify'), ['code' => $code])
            ->assertRedirect(route('cart.index'));

        $this->assertAuthenticated('customer');
        $this->assertSame('09120000091', Customer::query()->sole()->mobile);
        $this->assertNotNull(Customer::query()->sole()->mobile_verified_at);
        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_mediana_rejects_provider_failure_even_on_http_200(): void
    {
        Http::fake(['api.mediana.ir/*' => Http::response([
            'meta' => ['code' => 'BadRequest'],
            'data' => ['succeed' => false, 'requestId' => 1234],
        ])]);

        $this->post(route('customer.otp.request'), ['mobile' => '09120000092'])
            ->assertSessionHasErrors('mobile');
        $this->assertDatabaseCount('customer_login_codes', 0);
        $this->assertDatabaseCount('customers', 0);
    }

    public function test_mediana_rejects_missing_request_id_and_http_failure(): void
    {
        Http::fake(['api.mediana.ir/*' => Http::response([
            'meta' => ['code' => 'OK'],
            'data' => ['succeed' => true],
        ])]);

        $this->post(route('customer.otp.request'), ['mobile' => '09120000093'])
            ->assertSessionHasErrors('mobile');
        $this->assertDatabaseCount('customer_login_codes', 0);

        Http::fake(['api.mediana.ir/*' => Http::response($this->acceptedResponse(), 422)]);

        $this->post(route('customer.otp.request'), ['mobile' => '09120000094'])
            ->assertSessionHasErrors('mobile');
        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_missing_mediana_credentials_does_not_send_any_http_request(): void
    {
        config()->set('services.mediana.api_key', '');
        Http::fake();

        $this->post(route('customer.otp.request'), ['mobile' => '09120000095'])
            ->assertSessionHasErrors('mobile');

        Http::assertNothingSent();
        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_four_digit_otp_supports_leading_zero_and_only_three_bad_attempts(): void
    {
        CustomerLoginCode::query()->create([
            'mobile' => '09120000096',
            'code_hash' => Hash::make('0042'),
            'attempts' => 0,
            'expires_at' => now()->addSeconds(120),
            'last_sent_at' => now(),
        ]);

        $this->withSession(['customer_otp_mobile' => '09120000096'])
            ->post(route('customer.otp.verify'), ['code' => '۰۰۴۲'])
            ->assertRedirect(route('cart.index'));
        $this->assertAuthenticated('customer');

        $this->post(route('customer.logout'));
        CustomerLoginCode::query()->create([
            'mobile' => '09120000097',
            'code_hash' => Hash::make('0042'),
            'attempts' => 0,
            'expires_at' => now()->addSeconds(120),
            'last_sent_at' => now(),
        ]);

        $this->withSession(['customer_otp_mobile' => '09120000097']);
        for ($i = 0; $i < 3; $i++) {
            $this->post(route('customer.otp.verify'), ['code' => '9999'])
                ->assertSessionHasErrors('code');
        }

        $this->post(route('customer.otp.verify'), ['code' => '0042'])
            ->assertSessionHasErrors('code');
        $this->assertSame(3, CustomerLoginCode::query()->findOrFail('09120000097')->attempts);
    }

    private function acceptedResponse(): array
    {
        return [
            'meta' => ['code' => 'OK'],
            'data' => [
                'succeed' => true,
                'requestId' => 187484315,
                'status' => 'PendingApproval',
                'statusInt' => 2,
            ],
        ];
    }
}
