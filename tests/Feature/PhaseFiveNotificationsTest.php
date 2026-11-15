<?php

namespace Tests\Feature;

use App\Application\Notifications\CustomerNotificationText;
use App\Jobs\DeliverOutboxNotification;
use App\Models\Customer;
use App\Models\CustomerNotification;
use App\Models\NotificationLog;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Tests\TestCase;

class PhaseFiveNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_web_inbox_persists_each_outbox_event_once(): void
    {
        [$eventId, $order] = $this->orderAndEvent();

        $job = new DeliverOutboxNotification($eventId);
        $formatter = app(CustomerNotificationText::class);
        $job->handle($formatter);
        $job->handle($formatter);

        $this->assertDatabaseCount('customer_notifications', 1);
        $this->assertDatabaseCount('notification_logs', 1);
        $this->assertSame('web', NotificationLog::query()->sole()->channel);
        $this->assertSame('sent', NotificationLog::query()->sole()->status);
        $this->assertSame($order->customer_id, CustomerNotification::query()->sole()->customer_id);
        $this->assertNotNull(DB::table('outbox_events')->where('id', $eventId)->first()->processed_at);
    }

    public function test_notification_does_not_cross_customer_boundary(): void
    {
        [$eventId] = $this->orderAndEvent();
        $other = Customer::create(['mobile' => '09120000032', 'mobile_verified_at' => now()]);
        DB::table('outbox_events')->where('id', $eventId)->update([
            'payload' => json_encode(['order_id' => Order::query()->sole()->id, 'customer_id' => $other->id]),
        ]);

        $this->expectException(\RuntimeException::class);
        (new DeliverOutboxNotification($eventId))->handle(app(CustomerNotificationText::class));
    }

    private function orderAndEvent(): array
    {
        $customer = Customer::create(['mobile' => '09120000031', 'mobile_verified_at' => now()]);
        $order = Order::create([
            'order_number' => 'DN-WEB-1',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_status' => 'awaiting_payment',
            'payment_status' => 'awaiting_receipt',
            'recipient_name' => 'Dena Buyer',
            'recipient_mobile' => '09120000031',
            'province' => 'Kohgiluyeh',
            'city' => 'Sisakht',
            'address_line' => 'Example test address 12',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 1500000,
            'shipping_fee_irr' => 250000,
            'total_irr' => 1750000,
            'shipping_weight_grams' => 700,
            'shipping_rule_snapshot' => [],
            'payment_instructions_snapshot' => [],
        ]);
        $eventId = (string) Str::uuid();
        DB::table('outbox_events')->insert([
            'id' => $eventId,
            'aggregate_type' => 'order',
            'aggregate_id' => $order->id,
            'event_type' => 'order.created',
            'payload' => json_encode(['order_id' => $order->id, 'customer_id' => $customer->id]),
            'occurred_at' => now(),
        ]);

        return [$eventId, $order];
    }
}
