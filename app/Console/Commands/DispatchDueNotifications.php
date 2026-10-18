<?php

namespace App\Console\Commands;

use App\Jobs\DeliverOutboxNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchDueNotifications extends Command
{
    protected $signature = 'denapax:notifications:dispatch {--limit=100}';

    protected $description = 'Queue unsent or retryable transactional Telegram outbox notifications';

    public function handle(): int
    {
        $limit = min(500, max(1, (int) $this->option('limit')));

        $ids = DB::table('outbox_events')
            ->leftJoin('notification_logs', function ($join): void {
                $join->on('outbox_events.id', '=', 'notification_logs.outbox_event_id')
                    ->where('notification_logs.channel', '=', 'telegram');
            })
            ->whereNull('outbox_events.processed_at')
            ->where(function ($q): void {
                $q->whereNull('notification_logs.id')
                    ->orWhere(function ($retry): void {
                        $retry->where('notification_logs.attempts', '<', 5)
                            ->where(function ($due): void {
                                $due->whereIn('notification_logs.status', ['queued', 'failed'])
                                    ->where(function ($q): void {
                                        $q->whereNull('notification_logs.next_attempt_at')
                                            ->orWhere('notification_logs.next_attempt_at', '<=', now());
                                    })
                                    ->orWhere(function ($expired): void {
                                        $expired->where('notification_logs.status', 'sending')
                                            ->where('notification_logs.claimed_at', '<=', now()->subMinutes(3));
                                    });
                            });
                    });
            })
            ->orderBy('outbox_events.occurred_at')
            ->limit($limit)
            ->pluck('outbox_events.id');

        foreach ($ids as $id) {
            DeliverOutboxNotification::dispatch($id)->onQueue('notifications');
        }

        $this->info($ids->count().' event(s) queued.');

        return self::SUCCESS;
    }
}
