<?php

namespace App\Jobs;

use App\Application\Notifications\CustomerNotificationText;
use App\Models\CustomerNotification;
use App\Models\NotificationLog;
use App\Models\Order;
use App\Models\PaymentReceipt;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;
use Throwable;

class DeliverOutboxNotification implements ShouldQueue, ShouldBeUnique
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120];

    public int $timeout = 30;

    // The database queue retains jobs while workers are offline. A long unique
    // lock also prevents the scheduler from amplifying an already queued event.
    public int $uniqueFor = 604800;

    public function __construct(public readonly string $eventId) {}

    public function uniqueId(): string
    {
        return 'web-outbox:'.$this->eventId;
    }

    public function handle(CustomerNotificationText $formatter): void
    {
        try {
            $payload = $this->claimAttempt();

            if ($payload === null) {
                return;
            }

            DB::transaction(function () use ($formatter, $payload): void {
                $event = DB::table('outbox_events')
                    ->where('id', $this->eventId)
                    ->lockForUpdate()
                    ->first();

                if ($event === null || $event->processed_at !== null) {
                    return;
                }

                $log = NotificationLog::query()
                    ->where('outbox_event_id', $event->id)
                    ->where('channel', 'web')
                    ->lockForUpdate()
                    ->first();

                if ($log === null || $log->status !== 'sending') {
                    return;
                }

                $order = Order::query()->findOrFail((int) ($payload['order_id'] ?? 0));
                $customerId = (int) ($payload['customer_id'] ?? 0);

                if ($customerId !== (int) $order->customer_id || $customerId !== (int) $log->customer_id) {
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

                $log->update([
                    'status' => 'sent',
                    'claimed_at' => null,
                    'sent_at' => now(),
                    'next_attempt_at' => null,
                    'last_error' => null,
                ]);

                DB::table('outbox_events')->where('id', $event->id)->update(['processed_at' => now()]);
            }, 3);
        } catch (Throwable $exception) {
            $this->recordRetry($exception);

            throw $exception;
        }
    }

    public function failed(?Throwable $exception): void
    {
        NotificationLog::query()
            ->where('outbox_event_id', $this->eventId)
            ->where('channel', 'web')
            ->where('status', '!=', 'sent')
            ->update([
                'status' => 'failed',
                'claimed_at' => null,
                'next_attempt_at' => null,
                'last_error' => $exception ? $this->errorSummary($exception) : 'Delivery failed after exhausting retries.',
            ]);
    }

    private function claimAttempt(): ?array
    {
        return DB::transaction(function (): ?array {
            $event = DB::table('outbox_events')
                ->where('id', $this->eventId)
                ->lockForUpdate()
                ->first();

            if (! $event || $event->processed_at !== null) {
                return null;
            }

            $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);
            $customerId = (int) ($payload['customer_id'] ?? 0);

            if ($customerId < 1) {
                throw new \RuntimeException('Notification event has no valid customer recipient.');
            }

            $log = NotificationLog::query()->firstOrCreate(
                [
                    'outbox_event_id' => $event->id,
                    'customer_id' => $customerId,
                    'channel' => 'web',
                ],
                [
                    'status' => 'queued',
                    'attempts' => 0,
                ],
            );

            $log = NotificationLog::query()
                ->whereKey($log->getKey())
                ->lockForUpdate()
                ->firstOrFail();

            if (in_array($log->status, ['sent', 'failed'], true)) {
                return null;
            }

            $queueAttempt = max(1, $this->attempts());

            // Ignore a duplicate first delivery while the current worker owns
            // a fresh lease. A queue retry (attempt > 1) may take over a job
            // whose worker died after claiming it.
            if ($log->status === 'sending'
                && $log->claimed_at?->greaterThan(now()->subMinutes(10))
                && $queueAttempt === 1) {
                return null;
            }

            if ((int) $log->customer_id !== $customerId) {
                throw new \RuntimeException('Notification recipient mismatch.');
            }

            $log->update([
                'status' => 'sending',
                'attempts' => $log->attempts + 1,
                'claimed_at' => now(),
                'next_attempt_at' => now()->addMinutes(10),
                'sent_at' => null,
            ]);

            return $payload;
        }, 3);
    }

    private function recordRetry(Throwable $exception): void
    {
        $queueAttempt = max(1, $this->attempts());
        $backoff = $this->backoff[min($queueAttempt - 1, count($this->backoff) - 1)] ?? 120;

        NotificationLog::query()
            ->where('outbox_event_id', $this->eventId)
            ->where('channel', 'web')
            ->where('status', 'sending')
            ->update([
                'status' => 'queued',
                'claimed_at' => null,
                'next_attempt_at' => now()->addSeconds($backoff),
                'last_error' => $this->errorSummary($exception),
            ]);
    }

    private function errorSummary(Throwable $exception): string
    {
        $message = preg_replace('/[\r\n\t]+/', ' ', $exception->getMessage());

        return mb_substr(class_basename($exception).': '.trim((string) $message), 0, 200);
    }
}
