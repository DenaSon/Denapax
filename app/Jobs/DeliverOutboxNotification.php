<?php

namespace App\Jobs;

use App\Application\Notifications\CustomerNotificationText;
use App\Models\CustomerNotification;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class DeliverOutboxNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public function __construct(public readonly string $eventId) {}

    public function handle(CustomerNotificationText $formatter): void
    {
        DB::transaction(function () use ($formatter): void {
            $event = DB::table('outbox_events')->where('id', $this->eventId)->lockForUpdate()->first();

            if (! $event || $event->processed_at !== null) {
                return;
            }

            $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);
            $order = Order::query()->findOrFail((int) ($payload['order_id'] ?? 0));
            $customerId = (int) ($payload['customer_id'] ?? 0);

            if ($customerId !== $order->customer_id) {
                throw new \RuntimeException('Notification customer/order mismatch.');
            }

            $receipt = isset($payload['receipt_id'])
                ? PaymentReceipt::query()->where('order_id', $order->id)->findOrFail((int) $payload['receipt_id'])
                : null;

            [$title, $body] = $formatter->render($event->event_type, $order, $receipt);

            CustomerNotification::query()->firstOrCreate(
                ['outbox_event_id' => $event->id],
                ['customer_id' => $customerId, 'title' => $title, 'body' => $body],
            );

            NotificationLog::query()->updateOrCreate(
                ['outbox_event_id' => $event->id, 'customer_id' => $customerId, 'channel' => 'web'],
                ['status' => 'sent', 'attempts' => 1, 'claimed_at' => null, 'sent_at' => now(), 'next_attempt_at' => null, 'last_error' => null],
            );

            DB::table('outbox_events')->where('id', $event->id)->update(['processed_at' => now()]);
        }, 3);
    }
}
