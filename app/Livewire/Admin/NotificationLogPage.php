<?php

namespace App\Livewire\Admin;

use App\Jobs\DeliverOutboxNotification;
use App\Models\NotificationLog;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class NotificationLogPage extends Component
{
    use WithPagination;

    public string $status = 'failed';

    public function updatedStatus(): void
    {
        $this->resetPage();
    }

    public function retry(int $logId): void
    {
        $staff = auth()->user();

        abort_unless($staff?->is_active, 403);
        Gate::forUser($staff)->authorize('orders.update');

        $eventId = DB::transaction(function () use ($logId): ?string {
            $log = NotificationLog::query()
                ->where('channel', 'web')
                ->whereKey($logId)
                ->lockForUpdate()
                ->firstOrFail();

            if ($log->status !== 'failed') {
                $this->addError('retry', 'فقط اعلان‌های ناموفق امکان تلاش مجدد دارند.');

                return null;
            }

            $event = DB::table('outbox_events')
                ->where('id', $log->outbox_event_id)
                ->lockForUpdate()
                ->first();

            if (! $event || $event->processed_at !== null) {
                $this->addError('retry', 'رویداد این اعلان دیگر قابل پردازش نیست.');

                return null;
            }

            $log->update([
                'status' => 'queued',
                'claimed_at' => now(),
                'next_attempt_at' => now()->addMinutes(10),
                'sent_at' => null,
            ]);

            activity()->causedBy($staff)->performedOn($log)
                ->withProperties(['outbox_event_id' => $log->outbox_event_id, 'channel' => 'web'])
                ->log('notification.retry_requested');

            return (string) $log->outbox_event_id;
        }, 3);

        if ($eventId === null) {
            return;
        }

        DeliverOutboxNotification::dispatch($eventId)->onQueue('notifications');

        session()->flash('status', 'اعلان برای تلاش مجدد به صف ثبت در حساب مشتری فرستاده شد.');
    }

    public function render()
    {
        Gate::authorize('orders.view');

        return view('livewire.admin.notification-log-page', [
            'logs' => NotificationLog::query()
                ->join('outbox_events', 'outbox_events.id', '=', 'notification_logs.outbox_event_id')
                ->select(['notification_logs.*', 'outbox_events.event_type'])
                ->when(in_array($this->status, ['queued', 'sending', 'failed', 'sent'], true),
                    fn ($query) => $query->where('notification_logs.status', $this->status))
                ->latest('notification_logs.updated_at')
                ->paginate(15),
        ])->layout('layouts.admin');
    }
}
