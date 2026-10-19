<?php

namespace App\Livewire\Admin;

use App\Application\Actions\ReviewPaymentReceipt;
use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;
use Livewire\Attributes\Url;

class PaymentReviewPage extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    #[Url(as: 'receipt')]
    public ?int $receiptId = null;

    public string $rejectionReason = '';

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function approve(int $receiptId, ReviewPaymentReceipt $action): void
    {
        Gate::authorize('payments.review');

        $action->execute(auth()->user(), $receiptId, 'approved');
        session()->flash('status', 'پرداخت پس از بررسی واریز بانکی تأیید شد.');
    }

    public function reject(int $receiptId, ReviewPaymentReceipt $action): void
    {
        Gate::authorize('payments.review');

        $this->validate(['rejectionReason' => 'required|string|min:3|max:1500']);
        $action->execute(auth()->user(), $receiptId, 'rejected', $this->rejectionReason);
        $this->rejectionReason = '';
        session()->flash('status', 'رسید رد شد و دلیل برای ارسال به مشتری ثبت شد.');
    }

    public function render()
    {
        Gate::authorize('payments.view');

        return view('livewire.admin.payment-review-page', [
            'receipts' => PaymentReceipt::query()
                ->with(['order', 'reviewer'])
                ->when($this->receiptId !== null, fn ($query) => $query->whereKey($this->receiptId))
                ->when($this->receiptId === null && in_array($this->filter, ['pending', 'approved', 'rejected'], true),
                    fn ($query) => $query->where('status', $this->filter))
                ->latest('submitted_at')
                ->paginate(12),
        ])->layout('layouts.admin');
    }
}
