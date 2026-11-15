<?php

namespace Tests\Feature;

use App\Application\Actions\CreateOrder;
use App\Application\Actions\ManageCart;
use App\Application\Actions\PrepareCheckout;
use App\Models\Category;
use App\Models\Customer;
use App\Models\GatewayPaymentAttempt;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request as HttpClientRequest;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
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
        $this->assertSame('failed', GatewayPaymentAttempt::query()->sole()->status);
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
