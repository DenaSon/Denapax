<?php

namespace App\Console\Commands;

use App\Jobs\DeliverOutboxNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchDueNotifications extends Command
{
    protected $signature = 'denapax:notifications:dispatch {--limit=100}';

    protected $description = 'Queue undelivered web customer inbox events';

    public function handle(): int
    {
        $ids = DB::table('outbox_events')
            ->whereNull('processed_at')
            ->orderBy('occurred_at')
            ->limit(min(500, max(1, (int) $this->option('limit'))))
            ->pluck('id');

        foreach ($ids as $id) {
            DeliverOutboxNotification::dispatch($id)->onQueue('notifications');
        }

        $this->info($ids->count().' web notification(s) queued.');

        return self::SUCCESS;
    }
}
