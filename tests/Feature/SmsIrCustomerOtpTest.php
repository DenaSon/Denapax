<?php

namespace Tests\Feature;

use App\Application\Actions\CustomerOtpLogin;
use App\Integrations\Sms\ConfiguredSmsIrService;
use App\Models\CustomerLoginCode;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Ipe\Sdk\SmsIrService;
use Mockery;
use ReflectionProperty;
use RuntimeException;
use Tests\TestCase;

class SmsIrCustomerOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_smsir_uses_configured_verify_template_and_keeps_only_a_hashed_otp(): void
    {
        $this->configureSmsIr();

        $code = null;
        $sdk = Mockery::mock(SmsIrService::class);
        $sdk->shouldReceive('verifySend')
            ->once()
            ->withArgs(function (string $mobile, int $templateId, array $parameters) use (&$code): bool {
                $code = $parameters[0]['value'] ?? null;

                return $mobile === '09120000021'
                    && $templateId === 967976
                    && $parameters === [['name' => 'HOURS', 'value' => $code]]
                    && is_string($code)
                    && preg_match('/^[0-9]{6}$/D', $code) === 1;
            })
            ->andReturn((object) [
                'status' => 1,
                'message' => 'موفق',
                'data' => ['messageId' => 987654321, 'cost' => 1.0],
            ]);

        $this->app->instance(SmsIrService::class, $sdk);

        app(CustomerOtpLogin::class)->request('09120000021', '127.0.0.1');

        $challenge = CustomerLoginCode::query()->sole();

        $this->assertIsString($code);
        $this->assertTrue(Hash::check($code, $challenge->code_hash));
        $this->assertNotSame($code, $challenge->code_hash);
        $this->assertSame(0, $challenge->attempts);
        $this->assertSame('09120000021', $challenge->mobile);
    }

    public function test_smsir_rejects_a_non_success_api_status_even_when_a_message_id_is_returned(): void
    {
        $this->configureSmsIr();

        $sdk = Mockery::mock(SmsIrService::class);
        $sdk->shouldReceive('verifySend')
            ->once()
            ->andReturn((object) [
                'status' => 0,
                'data' => ['messageId' => 123456],
            ]);

        $this->app->instance(SmsIrService::class, $sdk);

        try {
            app(CustomerOtpLogin::class)->request('09120000022', '127.0.0.1');
            $this->fail('A non-success SMS.ir status must fail OTP delivery.');
        } catch (ValidationException $exception) {
            $this->assertSame(
                'ارسال کد در حال حاضر ممکن نیست. دوباره تلاش کنید.',
                $exception->errors()['mobile'][0],
            );
        }

        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_smsir_rejects_success_status_without_a_valid_message_id(): void
    {
        $this->configureSmsIr();

        $sdk = Mockery::mock(SmsIrService::class);
        $sdk->shouldReceive('verifySend')
            ->once()
            ->andReturn((object) [
                'status' => 1,
                'data' => ['cost' => 1.0],
            ]);

        $this->app->instance(SmsIrService::class, $sdk);

        try {
            app(CustomerOtpLogin::class)->request('09120000023', '127.0.0.1');
            $this->fail('A success envelope without a message ID must fail OTP delivery.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('mobile', $exception->errors());
        }

        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_smsir_provider_exceptions_are_sanitized_and_remove_the_otp_challenge(): void
    {
        $this->configureSmsIr();

        $sdk = Mockery::mock(SmsIrService::class);
        $sdk->shouldReceive('verifySend')
            ->once()
            ->andThrow(new RuntimeException('api-key=do-not-leak otp=123456'));

        $this->app->instance(SmsIrService::class, $sdk);

        try {
            app(CustomerOtpLogin::class)->request('09120000024', '127.0.0.1');
            $this->fail('Provider exceptions must fail OTP delivery.');
        } catch (ValidationException $exception) {
            $message = $exception->errors()['mobile'][0];

            $this->assertStringNotContainsString('do-not-leak', $message);
            $this->assertStringNotContainsString('123456', $message);
        }

        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_smsir_fails_closed_when_the_approved_template_is_not_configured(): void
    {
        $this->configureSmsIr();
        config()->set('services.smsir.verify_template_id', null);

        $sdk = Mockery::mock(SmsIrService::class);
        $sdk->shouldNotReceive('verifySend');
        $this->app->instance(SmsIrService::class, $sdk);

        try {
            app(CustomerOtpLogin::class)->request('09120000025', '127.0.0.1');
            $this->fail('SMS.ir must not be called without an approved template ID.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('mobile', $exception->errors());
        }

        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    public function test_smsir_service_uses_cached_config_and_finite_timeouts_without_redirects(): void
    {
        $this->configureSmsIr();

        $service = app(SmsIrService::class);

        $this->assertInstanceOf(ConfiguredSmsIrService::class, $service);

        $clientProperty = new ReflectionProperty(SmsIrService::class, 'client');
        $client = $clientProperty->getValue($service);

        $this->assertSame('https://api.sms.ir/v1/', (string) $client->getConfig('base_uri'));
        $this->assertSame(8.0, $client->getConfig('timeout'));
        $this->assertSame(3.0, $client->getConfig('connect_timeout'));
        $this->assertFalse($client->getConfig('allow_redirects'));
    }

    public function test_legacy_log_driver_fails_closed_without_logging_plaintext_otp(): void
    {
        config()->set('customer_auth.sms_driver', 'log');
        Log::spy();

        try {
            app(CustomerOtpLogin::class)->request('09120000026', '127.0.0.1');
            $this->fail('The legacy plaintext log driver must not send or log OTPs.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('mobile', $exception->errors());
        }

        Log::shouldNotHaveReceived('debug');
        $this->assertDatabaseCount('customer_login_codes', 0);
    }

    private function configureSmsIr(): void
    {
        config()->set('customer_auth.sms_driver', 'smsir');
        config()->set('services.smsir', [
            'api_key' => 'test-api-key',
            'verify_template_id' => '967976',
            'verify_parameter' => 'HOURS',
            'timeout' => 8.0,
            'connect_timeout' => 3.0,
        ]);
    }
}
