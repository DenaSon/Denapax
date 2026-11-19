<?php

namespace App\Console\Commands;

use App\Jobs\DeliverOutboxNotification;
use App\Jobs\SendPaymentApprovalSms;
use App\Models\NotificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchDueNotifications extends Command
{
    protected $signature = 'denapax:notifications:dispatch {--limit=100}';

    protected $description = 'Queue web inbox events and opt-in manual payment approval SMS';

    private const DISPATCH_LEASE_MINUTES = 10;

    public function handle(): int
    {
        $ids = DB::table('outbox_events')
            ->leftJoin('notification_logs', function ($join): void {
                $join->on('notification_logs.outbox_event_id', '=', 'outbox_events.id')
                    ->where('notification_logs.channel', '=', 'web');
            })
            ->whereNull('outbox_events.processed_at')
            ->where(function ($query): void {
                $query->whereNull('notification_logs.id')
                    ->orWhere(function ($query): void {
                        $query->whereIn('notification_logs.status', ['queued', 'sending'])
                            ->where(function ($query): void {
                                $query->whereNull('notification_logs.next_attempt_at')
                                    ->orWhere('notification_logs.next_attempt_at', '<=', now());
                            });
                    });
            })
            ->orderBy('outbox_events.occurred_at')
            ->limit(min(500, max(1, (int) $this->option('limit'))))
            ->pluck('outbox_events.id');

        $claimed = 0;

        foreach ($ids as $id) {
            $eventId = DB::transaction(function () use ($id): ?string {
                $event = DB::table('outbox_events')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if ($event === null || $event->processed_at !== null) {
                    return null;
                }

                $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);
                $customerId = filter_var($payload['customer_id'] ?? null, FILTER_VALIDATE_INT);

                if ($customerId === false || $customerId < 1) {
                    Log::warning('Outbox event has no valid customer recipient.', [
                        'outbox_event_id' => $event->id,
                    ]);

                    return null;
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

                // Failed deliveries are terminal until an authorized staff member
                // explicitly retries them. Sent rows are permanently idempotent.
                if (in_array($log->status, ['sent', 'failed'], true)) {
                    return null;
                }

                // A queued job is durable in the database queue. The lease only
                // recovers the rare commit-before-dispatch/process-crash window.
                // ShouldBeUnique prevents a second active job for this event.
                if ($log->next_attempt_at?->isFuture()
                    || ($log->status === 'sending'
                        && $log->claimed_at?->greaterThan(now()->subMinutes(self::DISPATCH_LEASE_MINUTES)))) {
                    return null;
                }

                $log->update([
                    'status' => 'queued',
                    'claimed_at' => now(),
                    'next_attempt_at' => now()->addMinutes(self::DISPATCH_LEASE_MINUTES),
                ]);

                return (string) $event->id;
            }, 3);

            if ($eventId !== null) {
                DeliverOutboxNotification::dispatch($eventId)->onQueue('notifications');
                $claimed++;
            }
        }

        $smsClaimed = $this->claimPaymentApprovalSms();

        $this->info($claimed.' web notification(s) and '.$smsClaimed.' payment approval SMS notification(s) claimed.');

        return self::SUCCESS;
    }

    /**
     * SMS is a separate delivery channel. Do not filter by outbox.processed_at:
     * the existing web inbox may have already processed the event.
     */
    private function claimPaymentApprovalSms(): int
    {
        if (! config('services.mediana.payment_approval_sms_enabled', false)) {
            return 0;
        }

        $ids = NotificationLog::query()
            ->where('channel', 'sms')
            ->where('status', 'queued')
            ->where('attempts', 0)
            ->where(function ($query): void {
                $query->whereNull('next_attempt_at')
                    ->orWhere('next_attempt_at', '<=', now());
            })
            ->orderBy('id')
            ->limit(min(500, max(1, (int) $this->option('limit'))))
            ->pluck('id');

        $claimed = 0;

        foreach ($ids as $id) {
            $logId = DB::transaction(function () use ($id): ?int {
                $log = NotificationLog::query()->whereKey($id)->lockForUpdate()->first();

                if ($log === null || $log->channel !== 'sms'
                    || $log->status !== 'queued' || $log->attempts !== 0
                    || $log->next_attempt_at?->isFuture()) {
                    return null;
                }

                // A long enough lease avoids repeatedly queuing the same job.
                // The worker also atomically rejects any duplicate attempt.
                $log->update([
                    'claimed_at' => now(),
                    'next_attempt_at' => now()->addMinutes(self::DISPATCH_LEASE_MINUTES),
                ]);

                return $log->id;
            }, 3);

            if ($logId !== null) {
                SendPaymentApprovalSms::dispatch($logId)->onQueue('notifications');
                $claimed++;
            }
        }

        return $claimed;
    }
}
