<?php

namespace App\Livewire\Admin;

use App\Models\NotificationLog;
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
