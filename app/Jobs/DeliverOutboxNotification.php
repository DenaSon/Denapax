<?php

namespace App\Jobs;

use App\Application\Notifications\TelegramNotificationText;
use App\Models\Customer;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use Throwable;

class DeliverOutboxNotification implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    private const MAX_ATTEMPTS = 5;

    public function __construct(public readonly string $eventId) {}

    public function handle(Nutgram $bot, TelegramNotificationText $formatter): void
    {
        $claim = DB::transaction(function (): ?array {
            $event = DB::table('outbox_events')->where('id', $this->eventId)->lockForUpdate()->first();
            if (! $event || $event->processed_at !== null) {
                return null;
            }

            $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);
            $customerId = (int) ($payload['customer_id'] ?? 0);
            if ($customerId < 1) {
                return null;
            }

            DB::table('notification_logs')->insertOrIgnore([
                'outbox_event_id' => $event->id,
                'customer_id' => $customerId,
                'channel' => 'telegram',
                'status' => 'queued',
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $log = NotificationLog::query()
                ->where('outbox_event_id', $event->id)
                ->where('customer_id', $customerId)
                ->where('channel', 'telegram')
                ->lockForUpdate()->firstOrFail();

            if ($log->status === 'sent'
                || $log->attempts >= self::MAX_ATTEMPTS
                || ($log->status === 'sending' && $log->claimed_at?->gt(now()->subMinutes(3)))
                || ($log->next_attempt_at && $log->next_attempt_at->isFuture())) {
                return null;
            }

            $attempt = $log->attempts + 1;
            $log->update([
                'status' => 'sending',
                'attempts' => $attempt,
                'claimed_at' => now(),
                'next_attempt_at' => null,
            ]);

            return ['event' => $event, 'payload' => $payload, 'log_id' => $log->id, 'attempt' => $attempt];
        }, 3);

        if (! $claim) {
            return;
        }

        $event = $claim['event'];
        try {
            if (! config('nutgram.token')) {
                throw new \RuntimeException('Telegram token unconfigured');
            }

            $customer = Customer::findOrFail((int) $claim['payload']['customer_id']);
            if (! $customer->telegram_chat_id) {
                throw new \RuntimeException('Telegram chat unavailable');
            }

            $order = Order::with('shipment')->findOrFail((int) $claim['payload']['order_id']);
            if ($order->customer_id !== $customer->id) {
                throw new \RuntimeException('Event customer mismatch');
            }

            $receipt = isset($claim['payload']['receipt_id'])
                ? PaymentReceipt::query()->where('order_id', $order->id)
                    ->findOrFail((int) $claim['payload']['receipt_id'])
                : null;

            $text = $formatter->render($event->event_type, $order, $receipt);
            $result = $bot->sendMessage(text: $text, chat_id: $customer->telegram_chat_id, parse_mode: ParseMode::HTML);

            DB::transaction(function () use ($claim, $result, $event): void {
                $updated = NotificationLog::query()
                    ->whereKey($claim['log_id'])->where('attempts', $claim['attempt'])
                    ->where('status', 'sending')
                    ->update([
                        'status' => 'sent',
                        'telegram_message_id' => $result?->message_id,
                        'last_error' => null,
                        'claimed_at' => null,
                        'sent_at' => now(),
                    ]);

                if ($updated) {
                    DB::table('outbox_events')->where('id', $event->id)->update(['processed_at' => now()]);
                }
            });
        } catch (Throwable $exception) {
            // Error *types* only, because HTTP messages may contain the bot token.
            NotificationLog::query()->whereKey($claim['log_id'])
                ->where('attempts', $claim['attempt'])->where('status', 'sending')
                ->update([
                    'status' => 'failed',
                    'last_error' => class_basename($exception),
                    'claimed_at' => null,
                    'next_attempt_at' => now()->addMinutes(min(60, 2 ** $claim['attempt'])),
                ]);
        }
    }
}
