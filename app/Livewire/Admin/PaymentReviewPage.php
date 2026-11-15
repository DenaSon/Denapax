<?php

namespace App\Livewire\Admin;

use App\Application\Actions\ReviewPaymentReceipt;
use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class PaymentReviewPage extends Component
{
    use WithPagination;

    public string $filter = 'pending';

    #[Url(as: 'receipt')]
    public ?int $receiptId = null;

    public string $rejectionReason = '';

    public ?int $rejectingReceiptId = null;

    public function updatedFilter(): void
    {
        $this->resetPage();
    }

    public function startRejection(int $receiptId): void
    {
        Gate::authorize('payments.review');
        $this->rejectingReceiptId = $receiptId;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function cancelRejection(): void
    {
        $this->rejectingReceiptId = null;
        $this->rejectionReason = '';
        $this->resetValidation();
    }

    public function approve(int $receiptId, ReviewPaymentReceipt $action): void
    {
        Gate::authorize('payments.review');

        $action->execute(auth()->user(), $receiptId, 'approved');
        $this->cancelRejection();
        session()->flash('status', 'پرداخت پس از بررسی واریز بانکی تأیید شد.');
    }

    public function reject(int $receiptId, ReviewPaymentReceipt $action): void
    {
        Gate::authorize('payments.review');

        if ($this->rejectingReceiptId !== null && $this->rejectingReceiptId !== $receiptId) {
            $this->addError('rejectionReason', 'برای رد این رسید، ابتدا همان رسید را انتخاب کنید.');

            return;
        }

        $this->validate(['rejectionReason' => 'required|string|min:3|max:1500']);
        $action->execute(auth()->user(), $receiptId, 'rejected', $this->rejectionReason);
        $this->cancelRejection();
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
