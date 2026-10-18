<?php

namespace Tests\Feature;

use App\Application\Actions\TransitionOrder;
use App\Models\Customer;
use App\Models\Order;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PhaseFiveFulfillmentTest extends TestCase
{
    use RefreshDatabase;

    private Order $order;

    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $customer = Customer::create(['telegram_user_id' => 54321]);
        $this->order = Order::create([
            'order_number' => 'DN-TEST-501',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_status' => 'confirmed',
            'payment_status' => 'approved',
            'recipient_name' => 'Test Buyer',
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran', 'city' => 'Tehran',
            'address_line' => 'A sample long address',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1000000,
            'shipping_fee_irr' => 500000,
            'total_irr' => 1500000,
            'shipping_weight_grams' => 700,
            'shipping_rule_snapshot' => ['base_weight_grams' => 1000],
            'payment_instructions_snapshot' => ['card_number' => '6037991234567890', 'card_holder_name' => 'Dena'],
        ]);

        $this->staff = User::factory()->create();
        $this->staff->assignRole('operator');
    }

    public function test_paid_order_can_only_move_in_sequence_with_tracking(): void
    {
        $transitions = app(TransitionOrder::class);

        try {
            $transitions->execute($this->staff, $this->order->id, 'ship', 'Iran Post', 'TRACK-1');
            $this->fail('Cannot ship before preparing.');
        } catch (ValidationException) {
            $this->assertSame('confirmed', $this->order->fresh()->order_status);
        }

        $transitions->execute($this->staff, $this->order->id, 'prepare');

        try {
            $transitions->execute($this->staff, $this->order->id, 'ship');
            $this->fail('Tracking and carrier are required.');
        } catch (ValidationException) {
            $this->assertSame('preparing', $this->order->fresh()->order_status);
        }

        $transitions->execute($this->staff, $this->order->id, 'ship', 'Iran Post', 'TRACK-1');
        $this->assertSame('TRACK-1', $this->order->fresh()->shipment->tracking_code);
        $transitions->execute($this->staff, $this->order->id, 'deliver');

        $this->assertSame('delivered', $this->order->fresh()->order_status);
        $this->assertNotNull($this->order->fresh()->shipment->delivered_at);
        $this->assertDatabaseCount('shipments', 1);
        $this->assertDatabaseCount('order_status_histories', 3);
        $this->assertDatabaseCount('outbox_events', 3);

        try {
            $transitions->execute($this->staff, $this->order->id, 'deliver');
            $this->fail('Duplicate delivery must be rejected.');
        } catch (ValidationException) {
            $this->assertDatabaseCount('outbox_events', 3);
        }
    }

    public function test_cancellation_only_for_unpaid_orders_and_admin_access_is_guarded(): void
    {
        $this->actingAs($this->staff)->get('/admin/orders')->assertOk();
        $this->actingAs($this->staff)->get('/admin/orders/'.$this->order->id)->assertOk();

        try {
            app(TransitionOrder::class)->execute($this->staff, $this->order->id, 'cancel');
            $this->fail('Paid order cannot be canceled.');
        } catch (ValidationException) {
            $this->assertSame('confirmed', $this->order->fresh()->order_status);
        }

        $this->order->update(['order_status' => 'awaiting_payment', 'payment_status' => 'rejected']);
        app(TransitionOrder::class)->execute($this->staff, $this->order->id, 'cancel');

        $this->assertSame('canceled', $this->order->fresh()->order_status);

        $unprivileged = User::factory()->create();
        $this->actingAs($unprivileged)->get('/admin/orders')->assertForbidden();
    }
}
