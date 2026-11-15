<?php

namespace App\Livewire\Admin;

use App\Application\Actions\ReconcileGatewayPayment;
use App\Models\GatewayPaymentAttempt;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class GatewayPaymentsPage extends Component
{
    use WithPagination;

    #[Url]
    public string $filter = 'unresolved';

    public array $resolutionNotes = [];

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function inquire(int $id, ReconcileGatewayPayment $reconciliation): void
    {
        Gate::authorize('payments.review');
        $attempt = $reconciliation->inquire(auth('web')->user(), $id);

        session()->flash('status', 'استعلام انجام شد؛ وضعیت فعلی: '.($attempt->inquiry_status ?: $attempt->status));
    }

    public function clearUnpaid(int $id, ReconcileGatewayPayment $reconciliation): void
    {
        Gate::authorize('payments.review');
        $note = (string) ($this->resolutionNotes[$id] ?? '');
        $reconciliation->clearUnpaid(auth('web')->user(), $id, $note);

        unset($this->resolutionNotes[$id]);
        session()->flash('status', 'پس از تطبیق بانکی، تلاش پرداخت به‌عنوان واریزنشده ثبت شد.');
    }

    public function render()
    {
        Gate::authorize('payments.view');

        return view('livewire.admin.gateway-payments-page', [
            'attempts' => GatewayPaymentAttempt::query()
                ->with(['order', 'resolver'])
                ->when($this->filter === 'unresolved',
                    fn ($query) => $query->whereNotIn('status', ['verified', 'cleared_unpaid']))
                ->when($this->filter === 'verified',
                    fn ($query) => $query->where('status', 'verified'))
                ->when($this->filter === 'cleared',
                    fn ($query) => $query->where('status', 'cleared_unpaid'))
                ->latest('id')
                ->paginate(15),
        ])->layout('layouts.admin');
    }
}
