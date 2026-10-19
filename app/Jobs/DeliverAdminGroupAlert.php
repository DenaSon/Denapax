<?php

namespace App\Jobs;

use App\Application\Notifications\AdminGroupAlertText;
use App\Models\AdminGroupAlertLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Support\AdminGroupChat;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Properties\ParseMode;
use Throwable;

class DeliverAdminGroupAlert implements ShouldQueue
{
    use Queueable;

    public int $tries = 1;

    private const MAX_ATTEMPTS = 5;

    public function __construct(public readonly string $eventId) {}

    public function handle(Nutgram $bot, AdminGroupAlertText $formatter): void
    {
        $chatId = AdminGroupChat::id();
        if ($chatId === null) {
            return;
        }

        $claim = DB::transaction(function () use ($chatId): ?array {
            $channel = DB::table('admin_group_alert_channels')->where('id', 1)->first();
            $event = DB::table('outbox_events')->where('id', $this->eventId)->lockForUpdate()->first();

            if (! $event || ! $channel || (int) $channel->chat_id !== $chatId
                || ! in_array($event->event_type, AdminGroupAlertText::EVENTS, true)
                || $event->occurred_at <= $channel->activated_at) {
                return null;
            }

            DB::table('admin_group_alert_logs')->insertOrIgnore([
                'outbox_event_id' => $event->id,
                'chat_id' => $chatId,
                'status' => 'queued',
                'attempts' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
            $log = AdminGroupAlertLog::query()
                ->where('outbox_event_id', $event->id)->lockForUpdate()->firstOrFail();

            // A group switch must not cause a previously queued message
            // to be delivered to a stale or accidentally changed recipient.
            if ((int) $log->chat_id !== $chatId
                || $log->status === 'sent'
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

            return [
                'event' => $event,
                'payload' => json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR),
                'log_id' => $log->id,
                'attempt' => $attempt,
            ];
        }, 3);

        if (! $claim) {
            return;
        }

        try {
            if (! config('nutgram.token')) {
                throw new \RuntimeException('Telegram token not configured');
            }

            if (app()->environment('production')
                && ! str_starts_with((string) config('app.url'), 'https://')) {
                throw new \RuntimeException('HTTPS admin URL must be configured');
            }

            $event = $claim['event'];
            $order = Order::findOrFail((int) ($claim['payload']['order_id'] ?? 0));
            $receipt = isset($claim['payload']['receipt_id'])
                ? PaymentReceipt::query()->where('order_id', $order->id)
                    ->findOrFail((int) $claim['payload']['receipt_id'])
                : null;

            if ((int) ($claim['payload']['customer_id'] ?? 0) !== (int) $order->customer_id
                || ($event->event_type === 'payment.receipt_submitted' && $receipt === null)) {
                throw new \RuntimeException('Outbox payload/order mismatch');
            }

            $result = $bot->sendMessage(
                text: $formatter->render($event->event_type, $order),
                chat_id: $chatId,
                parse_mode: ParseMode::HTML,
                reply_markup: $formatter->keyboard($event->event_type, $order, $receipt),
            );

            AdminGroupAlertLog::query()
                ->whereKey($claim['log_id'])
                ->where('attempts', $claim['attempt'])
                ->where('status', 'sending')
                ->update([
                    'status' => 'sent',
                    'telegram_message_id' => $result?->message_id,
                    'last_error' => null,
                    'claimed_at' => null,
                    'next_attempt_at' => null,
                    'sent_at' => now(),
                ]);
        } catch (Throwable $exception) {
            // Store the exception *type* only. Provider exception text may contain
            // token fragments or sensitive response payloads.
            AdminGroupAlertLog::query()
                ->whereKey($claim['log_id'])
                ->where('attempts', $claim['attempt'])
                ->where('status', 'sending')
                ->update([
                    'status' => 'failed',
                    'last_error' => class_basename($exception),
                    'claimed_at' => null,
                    'next_attempt_at' => now()->addMinutes(min(60, 2 ** $claim['attempt'])),
                ]);
        }
    }
}
