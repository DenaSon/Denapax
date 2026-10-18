<?php

namespace Tests\Feature;

use App\Application\Notifications\TelegramNotificationText;
use App\Jobs\DeliverOutboxNotification;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Mockery;
use SergiX44\Nutgram\Nutgram;
use Tests\TestCase;

class PhaseFiveNotificationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_outbox_delivery_is_recorded_once_and_deduplicated(): void
    {
        config(['nutgram.token' => 'testing-token']);
        $eventId = $this->seedOutbox('order.shipped');

        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendMessage')->once()->andReturnNull();
        $this->app->instance(Nutgram::class, $bot);

        $job = new DeliverOutboxNotification($eventId);
        $job->handle($bot, app(TelegramNotificationText::class));
        $job->handle($bot, app(TelegramNotificationText::class));

        $log = NotificationLog::query()->sole();
        $this->assertSame('sent', $log->status);
        $this->assertSame(1, $log->attempts);
        $this->assertNotNull(DB::table('outbox_events')->where('id', $eventId)->first()->processed_at);
    }

    public function test_transient_failure_records_retry_without_corrupting_order(): void
    {
        config(['nutgram.token' => 'testing-token']);
        $eventId = $this->seedOutbox('order.preparing');

        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendMessage')->once()->andThrow(new \RuntimeException('provider timeout'));
        $bot->shouldReceive('sendMessage')->once()->andReturnNull();
        $this->app->instance(Nutgram::class, $bot);

        $job = new DeliverOutboxNotification($eventId);
        $job->handle($bot, app(TelegramNotificationText::class));

        $log = NotificationLog::query()->sole();
        $this->assertSame('failed', $log->status);
        $this->assertSame('RuntimeException', $log->last_error);
        $this->assertSame(1, $log->attempts);

        $this->travel(3)->minutes();
        $job->handle($bot, app(TelegramNotificationText::class));
        $this->assertSame('sent', $log->fresh()->status);
        $this->assertSame(2, $log->fresh()->attempts);
        $this->travelBack();
    }

    private function seedOutbox(string $event): string
    {
        $customer = Customer::create([
            'telegram_user_id' => 20001,
            'telegram_chat_id' => 20001,
        ]);
        $order = Order::create([
            'order_number' => 'DN-NOTIF-1',
            'checkout_key' => (string) Str::uuid(),
            'customer_id' => $customer->id,
            'order_status' => 'preparing',
            'payment_status' => 'approved',
            'recipient_name' => 'Test',
            'recipient_mobile' => '09123456789',
            'province' => 'Tehran', 'city' => 'Tehran',
            'address_line' => 'long sample address',
            'postal_code' => '1234567890',
            'goods_subtotal_irr' => 100000,
            'shipping_fee_irr' => 20000,
            'total_irr' => 120000,
            'shipping_weight_grams' => 400,
            'shipping_rule_snapshot' => ['base_weight_grams' => 1000],
            'payment_instructions_snapshot' => ['card_number' => '6037991234567890', 'card_holder_name' => 'Dena'],
        ]);
        if ($event === 'order.shipped') {
            $order->shipment()->create(['carrier' => 'Post', 'tracking_code' => 'TRACK-001']);
        }

        $id = (string) Str::uuid();
        DB::table('outbox_events')->insert([
            'id' => $id,
            'aggregate_type' => 'order',
            'aggregate_id' => $order->id,
            'event_type' => $event,
            'payload' => json_encode(['order_id' => $order->id, 'customer_id' => $customer->id], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);

        return $id;
    }
}
