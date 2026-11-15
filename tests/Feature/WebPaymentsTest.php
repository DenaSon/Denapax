<?php

namespace Tests\Feature;

use App\Application\Actions\CreateOrder;
use App\Application\Actions\ManageCart;
use App\Application\Actions\PrepareCheckout;
use App\Application\Actions\ReconcileGatewayPayment;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GatewayPaymentAttempt;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class WebPaymentsTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Order $order;

    private string $authority;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();

        $this->customer = Customer::create(['mobile' => '09120000071', 'mobile_verified_at' => now()]);
        $address = $this->customer->addresses()->create([
            'recipient_name' => 'خریدار دنا',
            'recipient_mobile' => '09120000071',
            'province' => 'تهران',
            'city' => 'تهران',
            'address_line' => 'خیابان اصلی پلاک سیزده',
            'postal_code' => '1234567890',
            'is_default' => true,
        ]);
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'گردوی دنا',
            'slug' => 'dena-walnuts',
            'summary' => 'گردوی کوهستان دنا',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'sku' => 'WAL-200', 'title' => '۲۰۰ گرم',
            'sale_quantity' => 200, 'sale_unit' => 'g',
            'price_irr' => 2000000, 'shipping_weight_grams' => 250, 'is_available' => true,
        ]);
        ShippingSetting::create([
            'id' => 1, 'base_weight_grams' => 1000,
            'base_price_irr' => 500000, 'extra_step_grams' => 1000,
            'extra_step_price_irr' => 250000, 'packaging_weight_grams' => 150,
        ]);
        StoreSetting::create([
            'id' => 1, 'store_name' => 'دناپکس',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'مدیر فروشگاه',
        ]);
        app(ManageCart::class)->add($this->customer, $variant->id);
        $quote = app(PrepareCheckout::class)->execute($this->customer, $address->id);
        $this->order = app(CreateOrder::class)->execute(
            $this->customer,
            $address->id,
            (string) Str::uuid(),
            app(PrepareCheckout::class)->fingerprint($quote),
        );

        $this->authority = 'A'.str_repeat('1', 35);
        config()->set('zarinpal.enabled', true);
        config()->set('zarinpal.sandbox', true);
        config()->set('zarinpal.merchant_id', '12345678-1234-1234-1234-123456789012');
    }

    public function test_pending_gateway_shows_clear_warning_in_customer_order_list_and_payment_page(): void
    {
        $this->fakeGateway(200);
        $this->actingAs($this->customer, 'customer');

        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertRedirect();

        $this->get(route('orders.index'))->assertOk()
            ->assertSee('نتیجه پرداخت در حال بررسی')
            ->assertSee('مشاهده جزئیات و وضعیت سفارش');

        $this->get(route('orders.confirmation', $this->order->order_number))
            ->assertOk()
            ->assertSee('دوباره پرداخت یا کارت‌به‌کارت نکنید')
            ->assertSee('data-copy-value')
            ->assertDontSee('پرداخت امن با زرین‌پال');
    }

    public function test_gateway_amount_is_irr_and_server_verification_approves_once(): void
    {
        $this->fakeGateway(100, '987654321');
        $this->actingAs($this->customer, 'customer');

        $this->get(route('orders.confirmation', $this->order->order_number))
            ->assertOk()->assertSee('پرداخت امن با زرین‌پال');

        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/'.$this->authority);

        Http::assertSent(function (HttpClientRequest $request): bool {
            return str_ends_with($request->url(), '/request.json')
                && $request['amount'] === 2500000
                && $request['currency'] === 'IRR'
                && $request['merchant_id'] === '12345678-1234-1234-1234-123456789012'
                && $request['callback_url'] === route('payments.zarinpal.callback');
        });

        $this->get(route('payments.zarinpal.callback', ['Authority' => $this->authority, 'Status' => 'OK']))
            ->assertRedirect(route('orders.confirmation', $this->order->order_number));

        $this->assertSame('approved', $this->order->fresh()->payment_status);
        $this->assertSame('confirmed', $this->order->fresh()->order_status);
        $this->assertSame('verified', GatewayPaymentAttempt::query()->sole()->status);
        $this->assertSame('987654321', GatewayPaymentAttempt::query()->sole()->reference_id);
        $this->assertDatabaseCount('outbox_events', 2);
        $this->assertDatabaseCount('order_status_histories', 2);

        // Duplicate callback must not re-approve, re-emit notifications or call bank again.
        $this->get(route('payments.zarinpal.callback', ['Authority' => $this->authority, 'Status' => 'OK']))
            ->assertRedirect(route('orders.confirmation', $this->order->order_number));

        Http::assertSentCount(2);
        $this->assertDatabaseCount('gateway_payment_attempts', 1);
        $this->assertDatabaseCount('outbox_events', 2);
        $this->get(route('orders.confirmation', $this->order->order_number))->assertOk()
            ->assertSee('987654321')->assertSee('پرداخت تأیید شد');
    }

    public function test_forged_authority_and_gateway_failure_never_approve_order(): void
    {
        $this->fakeGateway(200);
        $this->actingAs($this->customer, 'customer');

        $this->get(route('payments.zarinpal.callback', ['Authority' => 'A'.str_repeat('9', 35), 'Status' => 'OK']))
            ->assertRedirect(route('orders.index'));

        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertRedirect();

        $this->get(route('payments.zarinpal.callback', ['Authority' => $this->authority, 'Status' => 'OK']))
            ->assertRedirect();

        $this->assertSame('awaiting_receipt', $this->order->fresh()->payment_status);
        $this->assertSame('verification_pending', GatewayPaymentAttempt::query()->sole()->status);
    }

    public function test_gateway_provider_timeout_leaves_payment_pending_and_blocks_duplicate_charge(): void
    {
        Http::fake(function (HttpClientRequest $request) {
            if (str_ends_with($request->url(), '/request.json')) {
                return Http::response(['data' => ['code' => 100, 'authority' => $this->authority]], 200);
            }

            return Http::response([], 503);
        });
        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();

        $this->get(route('payments.zarinpal.callback', ['Authority' => $this->authority, 'Status' => 'NOK']))
            ->assertRedirect();

        $this->assertSame('verification_pending', GatewayPaymentAttempt::query()->sole()->status);
        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertSessionHasErrors('payment');

        $this->assertSame('awaiting_receipt', $this->order->fresh()->payment_status);
        $this->assertDatabaseCount('gateway_payment_attempts', 1);
    }

    public function test_private_manual_receipt_upload_and_owner_scoped_media(): void
    {
        Storage::fake('receipts');
        config()->set('zarinpal.enabled', false);
        $this->actingAs($this->customer, 'customer');

        $this->post(route('orders.receipts.store', $this->order->order_number), [
            'receipt' => UploadedFile::fake()->image('receipt.png'),
        ])->assertRedirect(route('orders.confirmation', $this->order->order_number));

        $receipt = PaymentReceipt::query()->sole();
        $this->assertSame('under_review', $this->order->fresh()->payment_status);
        $this->assertSame('pending', $receipt->status);
        Storage::disk('receipts')->assertExists($receipt->storage_path);
        $this->get(route('orders.receipts.image', [
            'orderNumber' => $this->order->order_number, 'receipt' => $receipt->id,
        ]))->assertOk()->assertHeader('Cache-Control', 'max-age=0, no-store, private');

        $this->post(route('orders.receipts.store', $this->order->order_number), [
            'receipt' => UploadedFile::fake()->image('again.png'),
        ])->assertSessionHasErrors('receipt');

        $other = Customer::create(['mobile' => '09120000072', 'mobile_verified_at' => now()]);
        $this->actingAs($other, 'customer')
            ->get(route('orders.confirmation', $this->order->order_number))->assertNotFound();
        $this->get(route('orders.receipts.image', [
            'orderNumber' => $this->order->order_number, 'receipt' => $receipt->id,
        ]))->assertNotFound();
        $this->post(route('payments.zarinpal.start', $this->order->order_number))->assertNotFound();
    }

    public function test_live_gateway_attempt_blocks_competing_manual_receipt(): void
    {
        Storage::fake('receipts');
        $this->fakeGateway(100);
        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();

        $this->post(route('orders.receipts.store', $this->order->order_number), [
            'receipt' => UploadedFile::fake()->image('receipt.png'),
        ])->assertSessionHasErrors('receipt');

        $this->assertDatabaseCount('payment_receipts', 0);
        $this->assertSame('awaiting_receipt', $this->order->fresh()->payment_status);
    }

    public function test_unknown_old_payment_blocks_both_payment_methods_until_bank_reconciled(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('super-admin');

        $secondAuthority = 'A'.str_repeat('2', 35);
        $requests = 0;

        Http::fake(function (HttpClientRequest $request) use (&$requests, $secondAuthority) {
            if (str_ends_with($request->url(), '/request.json')) {
                $requests++;

                return Http::response(['data' => [
                    'code' => 100, 'authority' => $requests === 1 ? $this->authority : $secondAuthority,
                ]]);
            }

            if (str_ends_with($request->url(), '/inquiry.json')) {
                return Http::response(['data' => ['code' => 100, 'status' => 'FAILED']]);
            }

            return Http::response(['data' => ['code' => -51]]);
        });

        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();

        $attempt = GatewayPaymentAttempt::query()->sole();
        $attempt->forceFill(['created_at' => now()->subHours(3)])->save();

        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertSessionHasErrors('payment');
        $this->assertDatabaseCount('gateway_payment_attempts', 1);

        Storage::fake('receipts');
        $this->post(route('orders.receipts.store', $this->order->order_number), [
            'receipt' => UploadedFile::fake()->image('another.png'),
        ])->assertSessionHasErrors('receipt');

        $service = app(ReconcileGatewayPayment::class);

        try {
            $service->clearUnpaid($reviewer, $attempt->id, 'بررسی دستی فقط در پنل پذیرنده انجام شده است');
            $this->fail('Reconciliation must require fresh provider inquiry.');
        } catch (ValidationException) {
            $this->assertSame('redirected', $attempt->fresh()->status);
        }

        $checked = $service->inquire($reviewer, $attempt->id);
        $this->assertSame('FAILED', $checked->inquiry_status);
        $cleared = $service->clearUnpaid($reviewer, $attempt->id, 'مستند پنل پذیرنده شماره ۱۲۳ و تأیید عدم واریز');
        $this->assertSame('cleared_unpaid', $cleared->status);
        $this->assertSame($reviewer->id, $cleared->resolved_by);
        $this->assertNotNull($cleared->resolved_at);

        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertRedirect('https://sandbox.zarinpal.com/pg/StartPay/'.$secondAuthority);
        $this->assertDatabaseCount('gateway_payment_attempts', 2);
        $this->assertSame(2, $requests);
    }

    public function test_late_provider_success_after_manual_clearance_requires_review_not_double_approval(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('super-admin');

        $secondAuthority = 'A'.str_repeat('2', 35);
        $requests = 0;

        Http::fake(function (HttpClientRequest $request) use (&$requests, $secondAuthority) {
            if (str_ends_with($request->url(), '/request.json')) {
                $requests++;

                return Http::response(['data' => [
                    'code' => 100, 'authority' => $requests === 1 ? $this->authority : $secondAuthority,
                ]]);
            }

            if (str_ends_with($request->url(), '/inquiry.json')) {
                return Http::response(['data' => ['code' => 100, 'status' => 'FAILED']]);
            }

            return Http::response(['data' => ['code' => 100, 'ref_id' => '8123456123']]);
        });

        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();
        $first = GatewayPaymentAttempt::query()->sole();
        app(ReconcileGatewayPayment::class)->inquire($reviewer, $first->id);
        app(ReconcileGatewayPayment::class)->clearUnpaid(
            $reviewer, $first->id, 'مستند پنل پذیرنده شماره ۱۲۳ و تطبیق عدم واریز',
        );

        $this->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();
        $this->get(route('payments.zarinpal.callback', ['Authority' => $this->authority, 'Status' => 'OK']))
            ->assertRedirect(route('orders.confirmation', $this->order->order_number));

        $this->assertSame('requires_review', $first->fresh()->status);
        $this->assertSame('8123456123', $first->fresh()->reference_id);
        $this->assertSame('awaiting_payment', $this->order->fresh()->order_status);
        $this->assertDatabaseCount('outbox_events', 1);

        // Previous settled Authority must prevent any further charging.
        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertSessionHasErrors('payment');
        $this->get(route('orders.confirmation', $this->order->order_number))
            ->assertOk()->assertSee('دوباره پرداخت نکنید');
    }

    public function test_unknown_request_without_authority_needs_operator_evidence_after_cooling_period(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('super-admin');
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        Http::fake(['*' => Http::response([], 503)]);

        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertSessionHasErrors('payment');

        $attempt = GatewayPaymentAttempt::query()->sole();
        $this->assertSame('request_unknown', $attempt->status);
        $this->assertNull($attempt->authority);
        $this->post(route('payments.zarinpal.start', $this->order->order_number))
            ->assertSessionHasErrors('payment');

        $service = app(ReconcileGatewayPayment::class);
        try {
            $service->clearUnpaid($reviewer, $attempt->id, 'بررسی پنل پذیرنده و گزارش عدم دریافت وجه');
            $this->fail('Cooling period required when authority was not received.');
        } catch (ValidationException) {
            $this->assertSame('request_unknown', $attempt->fresh()->status);
        }

        $attempt->forceFill(['created_at' => now()->subHours(2)])->save();

        try {
            $service->clearUnpaid($operator, $attempt->id, 'بررسی پنل پذیرنده و گزارش عدم دریافت وجه');
            $this->fail('Only reviewers may clear money-related exceptions.');
        } catch (AuthorizationException) {
            $this->assertSame('request_unknown', $attempt->fresh()->status);
        }

        $cleared = $service->clearUnpaid($reviewer, $attempt->id, 'بررسی پنل پذیرنده و گزارش عدم دریافت وجه');
        $this->assertSame('cleared_unpaid', $cleared->status);

        $this->actingAs($operator, 'web')
            ->get(route('admin.payments.gateway'))->assertOk()->assertSee('تراکنش‌های زرین‌پال');
        $this->actingAs(User::factory()->create(), 'web')
            ->get(route('admin.payments.gateway'))->assertForbidden();
    }

    public function test_paid_inquiry_triggers_server_verification_and_cannot_be_cleared(): void
    {
        $this->seed(RolesAndPermissionsSeeder::class);
        $reviewer = User::factory()->create();
        $reviewer->assignRole('super-admin');

        Http::fake(function (HttpClientRequest $request) {
            if (str_ends_with($request->url(), '/request.json')) {
                return Http::response(['data' => ['code' => 100, 'authority' => $this->authority]]);
            }

            if (str_ends_with($request->url(), '/inquiry.json')) {
                return Http::response(['data' => ['code' => 100, 'status' => 'PAID']]);
            }

            return Http::response(['data' => ['code' => 101, 'ref_id' => '54123456']]);
        });

        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();
        $attempt = GatewayPaymentAttempt::query()->sole();

        $confirmed = app(ReconcileGatewayPayment::class)->inquire($reviewer, $attempt->id);
        $this->assertSame('verified', $confirmed->status);
        $this->assertSame('PAID', $confirmed->inquiry_status);
        $this->assertSame('approved', $this->order->fresh()->payment_status);

        $this->expectException(ValidationException::class);
        app(ReconcileGatewayPayment::class)->clearUnpaid(
            $reviewer, $attempt->id, 'تلاش برای آزادسازی تراکنش قطعی تأییدشده',
        );
    }

    public function test_verification_token_prevents_stale_network_response_from_overwriting_new_claim(): void
    {
        Http::fake(function (HttpClientRequest $request) {
            if (str_ends_with($request->url(), '/request.json')) {
                return Http::response(['data' => ['code' => 100, 'authority' => $this->authority]]);
            }

            GatewayPaymentAttempt::query()->where('authority', $this->authority)
                ->update(['verification_token' => (string) Str::uuid()]);

            return Http::response(['data' => ['code' => 100, 'ref_id' => '99112233']]);
        });

        $this->actingAs($this->customer, 'customer')
            ->post(route('payments.zarinpal.start', $this->order->order_number))->assertRedirect();

        $this->get(route('payments.zarinpal.callback', ['Authority' => $this->authority, 'Status' => 'OK']))
            ->assertRedirect();

        $this->assertSame('awaiting_receipt', $this->order->fresh()->payment_status);
        $this->assertSame('verifying', GatewayPaymentAttempt::query()->sole()->status);
        $this->assertDatabaseCount('outbox_events', 1);
    }

    private function fakeGateway(int $verificationCode, string $reference = '135792468'): void
    {
        Http::fake(function (HttpClientRequest $request) use ($verificationCode, $reference) {
            if (str_ends_with($request->url(), '/request.json')) {
                return Http::response(['data' => ['code' => 100, 'authority' => $this->authority]], 200);
            }

            return Http::response(['data' => ['code' => $verificationCode, 'ref_id' => $reference]], 200);
        });
    }
}
