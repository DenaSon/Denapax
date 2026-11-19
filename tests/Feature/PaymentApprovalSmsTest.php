<?php

namespace Tests\Feature;

use App\Application\Actions\ReviewPaymentReceipt;
use App\Integrations\Sms\MedianaPaymentApprovalSmsSender;
use App\Jobs\SendPaymentApprovalSms;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentApprovalSmsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Order $order;

    private PaymentReceipt $receipt;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $this->customer = Customer::create([
            'mobile' => '09120000031',
            'mobile_verified_at' => now(),
        ]);

        $this->order = Order::create([
            'order_number' => 'DN-SMS-APPROVAL-1',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'under_review',
            'recipient_name' => 'تحویل گیرنده متفاوت',
            'recipient_mobile' => '09129999999',
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => 'خیابان اصلی کوچه دوم پلاک یک',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1000000,
            'shipping_fee_irr' => 200000,
            'total_irr' => 1200000,
            'shipping_weight_grams' => 550,
            'shipping_rule_snapshot' => [],
            'payment_instructions_snapshot' => [],
        ]);

        $this->receipt = $this->order->receipts()->create([
            'attempt_number' => 1,
            'storage_path' => 'private/receipts/test-fixture.png',
            'status' => 'pending',
            'submitted_at' => now(),
        ]);

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('super-admin');

        config()->set('services.mediana.api_key', 'mediana-test-key');
        config()->set('services.mediana.timeout', 8);
        config()->set('services.mediana.connect_timeout', 3);
        config()->set('services.mediana.payment_approval_sms_enabled', true);
        config()->set('services.mediana.payment_approval_pattern_code', '');
        config()->set('services.mediana.payment_approval_pattern_parameter', 'ORDER');
    }

    public function test_approved_manual_receipt_queues_one_sms_for_verified_login_mobile(): void
    {
        $this->approve();

        $this->assertSame('confirmed', $this->order->fresh()->order_status);
        $this->assertSame('approved', $this->order->fresh()->payment_status);
        $this->assertDatabaseCount('notification_logs', 1);
        $log = NotificationLog::query()->sole();

        $this->assertSame('sms', $log->channel);
        $this->assertSame('queued', $log->status);
        $this->assertSame($this->customer->id, $log->customer_id);
        $this->assertSame('payment.approved', DB::table('outbox_events')->where('id', $log->outbox_event_id)->value('event_type'));

        // The web inbox may have already consumed the outbox event; SMS is separate.
        DB::table('outbox_events')->where('id', $log->outbox_event_id)->update(['processed_at' => now()]);
        Queue::fake();

        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);
        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);
        Queue::assertPushedOn('notifications', SendPaymentApprovalSms::class);
        Queue::assertPushed(SendPaymentApprovalSms::class, 1);

        Http::fake([
            'api.mediana.ir/*' => Http::response([
                'meta' => ['code' => 'OK'],
                'data' => ['succeed' => true, 'requestId' => 187484315],
            ]),
        ]);

        $job = new SendPaymentApprovalSms($log->id);
        $job->handle(app(MedianaPaymentApprovalSmsSender::class));
        $job->handle(app(MedianaPaymentApprovalSmsSender::class));

        Http::assertSentCount(1);
        Http::assertSent(fn (HttpClientRequest $request): bool => $request->url() === 'https://api.mediana.ir/sms/v1/send/sms'
            && $request->hasHeader('X-API-KEY', 'mediana-test-key')
            && $request['type'] === 'Informational'
            && $request['recipients'] === ['09120000031']
            && str_contains($request['messageText'], $this->order->public_reference)
            && ! str_contains($request['messageText'], '09129999999'));

        $this->assertSame('sent', $log->fresh()->status);
        $this->assertSame(1, $log->fresh()->attempts);
        $this->assertNotNull($log->fresh()->sent_at);
    }

    public function test_dedicated_approved_pattern_sends_only_order_reference(): void
    {
        config()->set('services.mediana.payment_approval_pattern_code', '918273');
        config()->set('services.mediana.payment_approval_pattern_parameter', 'ORDER');
        $this->approve();

        Http::fake([
            'api.mediana.ir/*' => Http::response([
                'meta' => ['code' => 'OK'],
                'data' => ['succeed' => true, 'requestId' => 1],
            ]),
        ]);

        (new SendPaymentApprovalSms(NotificationLog::query()->sole()->id))
            ->handle(app(MedianaPaymentApprovalSmsSender::class));

        Http::assertSent(fn (HttpClientRequest $request): bool => $request->url() === 'https://api.mediana.ir/sms/v1/send/pattern'
            && $request['patternCode'] === '918273'
            && $request['recipients'] === ['09120000031']
            && $request['parameters'] === ['ORDER' => $this->order->public_reference]
            && ! isset($request['otpCode']));

        $this->assertSame('sent', NotificationLog::query()->sole()->status);
    }

    public function test_rejected_or_duplicate_review_never_queues_payment_sms(): void
    {
        app(ReviewPaymentReceipt::class)->execute($this->reviewer, $this->receipt->id, 'rejected', 'واریز در بانک یافت نشد');

        $this->assertSame('rejected', $this->order->fresh()->payment_status);
        $this->assertDatabaseCount('notification_logs', 0);

        // The previous receipt is no longer reviewable.
        try {
            $this->approve();
            $this->fail('A rejected receipt must not be approved.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('notification_logs', 0);
        }
    }

    public function test_disabled_feature_preserves_approval_and_never_backfills_sms(): void
    {
        config()->set('services.mediana.payment_approval_sms_enabled', false);
        $this->approve();

        $this->assertSame('approved', $this->order->fresh()->payment_status);
        $this->assertDatabaseCount('notification_logs', 0);

        config()->set('services.mediana.payment_approval_sms_enabled', true);
        Queue::fake();
        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);

        Queue::assertNotPushed(SendPaymentApprovalSms::class);
    }

    public function test_provider_failure_is_terminal_and_does_not_undo_payment(): void
    {
        $this->approve();
        $log = NotificationLog::query()->sole();

        Http::fake([
            'api.mediana.ir/*' => Http::response([
                'meta' => ['code' => 'BadRequest'],
                'data' => null,
            ], 400),
        ]);

        try {
            (new SendPaymentApprovalSms($log->id))->handle(app(MedianaPaymentApprovalSmsSender::class));
            $this->fail('Mediana must reject the API request.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('not accepted', $exception->getMessage());
        }

        $this->assertSame('failed', $log->fresh()->status);
        $this->assertSame(1, $log->fresh()->attempts);
        $this->assertSame('approved', $this->order->fresh()->payment_status);
        Queue::fake();

        $this->artisan('denapax:notifications:dispatch')->assertExitCode(0);
        Queue::assertNotPushed(SendPaymentApprovalSms::class);
    }

    private function approve(): void
    {
        app(ReviewPaymentReceipt::class)->execute($this->reviewer, $this->receipt->id, 'approved');
    }
}
