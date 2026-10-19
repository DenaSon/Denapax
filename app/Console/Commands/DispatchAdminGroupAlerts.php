<?php

namespace App\Console\Commands;

use App\Jobs\DeliverAdminGroupAlert;
use App\Support\AdminGroupChat;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class DispatchAdminGroupAlerts extends Command
{
    protected $signature = 'denapax:admin-alerts:dispatch {--limit=100}';

    protected $description = 'Queue independent operational alerts for the configured private Telegram group';

    public function handle(): int
    {
        $chatId = AdminGroupChat::id();
        if ($chatId === null) {
            $this->info('Admin group alerts disabled (TELEGRAM_ADMIN_CHAT_ID not configured).');

            return self::SUCCESS;
        }

        $activatedAt = DB::transaction(function () use ($chatId) {
            // First enablement is a persistent cutover: no flood of historical orders.
            DB::table('admin_group_alert_channels')->insertOrIgnore([
                'id' => 1,
                'chat_id' => $chatId,
                'activated_at' => now(),
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            $channel = DB::table('admin_group_alert_channels')->where('id', 1)->lockForUpdate()->first();
            if ((int) $channel->chat_id !== $chatId) {
                // Repointing to another group only affects events created after the switch.
                DB::table('admin_group_alert_channels')->where('id', 1)->update([
                    'chat_id' => $chatId, 'activated_at' => now(), 'updated_at' => now(),
                ]);

                return now();
            }

            return $channel->activated_at;
        }, 3);

        $ids = DB::table('outbox_events')
            ->leftJoin('admin_group_alert_logs', 'outbox_events.id', '=', 'admin_group_alert_logs.outbox_event_id')
            ->whereIn('outbox_events.event_type', [
                'order.created', 'payment.receipt_submitted', 'payment.approved',
            ])
            // Intentionally NOT filtered by processed_at: customer delivery is independent.
            ->where('outbox_events.occurred_at', '>', $activatedAt)
            ->where(function ($query): void {
                $query->whereNull('admin_group_alert_logs.id')
                    ->orWhere(function ($retry): void {
                        $retry->where('admin_group_alert_logs.attempts', '<', 5)
                            ->where(function ($due): void {
                                $due->whereIn('admin_group_alert_logs.status', ['queued', 'failed'])
                                    ->where(function ($time): void {
                                        $time->whereNull('admin_group_alert_logs.next_attempt_at')
                                            ->orWhere('admin_group_alert_logs.next_attempt_at', '<=', now());
                                    })
                                    ->orWhere(function ($stale): void {
                                        $stale->where('admin_group_alert_logs.status', 'sending')
                                            ->where('admin_group_alert_logs.claimed_at', '<=', now()->subMinutes(3));
                                    });
                            });
                    });
            })
            ->orderBy('outbox_events.occurred_at')
            ->limit(min(500, max(1, (int) $this->option('limit'))))
            ->pluck('outbox_events.id');

        foreach ($ids as $id) {
            DeliverAdminGroupAlert::dispatch($id)->onQueue('notifications');
        }

        $this->info($ids->count().' admin group alert(s) queued.');

        return self::SUCCESS;
    }
}
