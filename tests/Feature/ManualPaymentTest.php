<?php

namespace Tests\Feature;

use App\Application\Actions\ReviewPaymentReceipt;
use App\Application\Actions\SubmitPaymentReceipt;
use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ManualPaymentTest extends TestCase
{
    use RefreshDatabase;

    private Customer $customer;

    private Order $order;

    private User $reviewer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        Storage::fake('receipts');

        $this->customer = Customer::create(['telegram_user_id' => 9876]);
        $this->order = Order::create([
            'order_number' => 'DN-PAYMENT-001',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $this->customer->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'awaiting_receipt',
            'recipient_name' => 'Buyer',
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran',
            'city' => 'Tehran',
            'address_line' => 'A valid street address',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1000000,
            'shipping_fee_irr' => 200000,
            'total_irr' => 1200000,
            'shipping_weight_grams' => 500,
            'shipping_rule_snapshot' => ['base_weight_grams' => 1000],
            'payment_instructions_snapshot' => [
                'card_number' => '6037991234567890',
                'card_holder_name' => 'Dena Store',
            ],
        ]);

        $this->reviewer = User::factory()->create();
        $this->reviewer->assignRole('super-admin');
    }

    public function test_rejected_receipt_can_be_resubmitted_and_only_second_attempt_approved(): void
    {
        $first = $this->submit('first.png');
        $this->assertSame('under_review', $this->order->fresh()->payment_status);

        $this->expectPendingUploadToBeRejected();

        $review = app(ReviewPaymentReceipt::class);
        $review->execute($this->reviewer, $first->id, 'rejected', 'Reference number does not match');
        $this->assertSame('rejected', $this->order->fresh()->payment_status);

        $second = $this->submit('second.png');
        $this->assertSame(2, $second->attempt_number);
        $review->execute($this->reviewer, $second->id, 'approved');

        $this->assertSame('approved', $this->order->fresh()->payment_status);
        $this->assertSame('confirmed', $this->order->fresh()->order_status);
        $this->assertSame('rejected', $first->fresh()->status);
        $this->assertSame('approved', $second->fresh()->status);
        $this->assertCount(2, $this->order->receipts);
        $this->assertDatabaseCount('outbox_events', 4);
        $this->assertDatabaseHas('order_status_histories', [
            'order_id' => $this->order->id,
            'from_status' => 'awaiting_payment',
            'to_status' => 'confirmed',
            'admin_user_id' => $this->reviewer->id,
        ]);

        try {
            $review->execute($this->reviewer, $second->id, 'approved');
            $this->fail('Reviewed receipt must not be approved again.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('outbox_events', 4);
        }
    }

    public function test_receipt_review_requires_permission_and_private_image_is_not_public(): void
    {
        $receipt = $this->submit('private.png');

        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->get('/admin/payments/receipts/'.$receipt->id.'/image')->assertRedirect('/login');
        $this->actingAs($operator)->get('/admin/payments')->assertOk();
        $this->actingAs($operator)->get('/admin/payments/receipts/'.$receipt->id.'/image')->assertOk();
        // Render the reviewer-only approval controls as well.
        $this->actingAs($this->reviewer)->get('/admin/payments')->assertOk();

        $this->expectException(AuthorizationException::class);
        app(ReviewPaymentReceipt::class)->execute($operator, $receipt->id, 'approved');
    }

    public function test_another_customer_cannot_submit_or_review_without_reason(): void
    {
        $someoneElse = Customer::create(['telegram_user_id' => 22222]);
        $path = $this->imagePath('stranger.png');

        try {
            app(SubmitPaymentReceipt::class)->execute($someoneElse, $this->order->id, $path);
            $this->fail('Another customer must not submit an order receipt.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payment_receipts', 0);
        }

        $receipt = $this->submit('legit.png');
        $this->expectException(ValidationException::class);
        app(ReviewPaymentReceipt::class)->execute($this->reviewer, $receipt->id, 'rejected', '');
    }

    private function expectPendingUploadToBeRejected(): void
    {
        $path = $this->imagePath('pending.png');

        try {
            app(SubmitPaymentReceipt::class)->execute($this->customer, $this->order->id, $path);
            $this->fail('An active pending receipt must block another attempt.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('payment_receipts', 1);
        }
    }

    private function submit(string $label): PaymentReceipt
    {
        return app(SubmitPaymentReceipt::class)->execute(
            $this->customer, $this->order->id, $this->imagePath($label), 'telegram-file-id',
        );
    }

    private function imagePath(string $label): string
    {
        $path = 'payment-receipts/'.Str::uuid().'.png';
        // A valid 1x1 image; no real customer data is stored in tests.
        Storage::disk('receipts')->put($path, base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9FZtgAAAAASUVORK5CYII=',
        ));

        return $path;
    }
}
