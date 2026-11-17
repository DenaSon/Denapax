<?php

namespace App\Console\Commands;

use App\Jobs\DeliverOutboxNotification;
use App\Models\NotificationLog;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchDueNotifications extends Command
{
    protected $signature = 'denapax:notifications:dispatch {--limit=100}';

    protected $description = 'Queue undelivered web customer inbox events';

    private const DISPATCH_LEASE_MINUTES = 10;

    public function handle(): int
    {
        $ids = DB::table('outbox_events')
            ->whereNull('processed_at')
            ->orderBy('occurred_at')
            ->limit(min(500, max(1, (int) $this->option('limit'))))
            ->pluck('id');

        $claimed = 0;

        foreach ($ids as $id) {
            $scheduled = DB::transaction(function () use ($id): bool {
                $event = DB::table('outbox_events')
                    ->where('id', $id)
                    ->lockForUpdate()
                    ->first();

                if (! $event || $event->processed_at !== null) {
                    return false;
                }

                $payload = json_decode($event->payload, true, 512, JSON_THROW_ON_ERROR);
                $customerId = filter_var($payload['customer_id'] ?? null, FILTER_VALIDATE_INT);

                if (! $customerId || $customerId < 1) {
                    Log::warning('Outbox event has no valid customer recipient.', [
                        'outbox_event_id' => $event->id,
                    ]);

                    return false;
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
                    return false;
                }

                // A queued job is durable in the database queue. The lease only
                // recovers the rare commit-before-dispatch/process-crash window.
                // ShouldBeUnique prevents a second active job for this event.
                if ($log->next_attempt_at?->isFuture()
                    || ($log->status === 'sending'
                        && $log->claimed_at?->greaterThan(now()->subMinutes(self::DISPATCH_LEASE_MINUTES)))) {
                    return false;
                }

                $log->update([
                    'status' => 'queued',
                    'claimed_at' => now(),
                    'next_attempt_at' => now()->addMinutes(self::DISPATCH_LEASE_MINUTES),
                ]);

                DeliverOutboxNotification::dispatch((string) $event->id)
                    ->onQueue('notifications')
                    ->afterCommit();

                return true;
            }, 3);

            if ($scheduled) {
                $claimed++;
            }
        }

        $this->info($claimed.' web notification(s) claimed for dispatch.');

        return self::SUCCESS;
    }
}
