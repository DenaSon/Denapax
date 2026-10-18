<?php

namespace App\Application\Actions;

use App\Models\Order;
use App\Models\PaymentReceipt;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ReviewPaymentReceipt
{
    public function __construct(private readonly RecordPaymentEvent $events) {}

    public function execute(User $reviewer, int $receiptId, string $decision, ?string $reason = null): PaymentReceipt
    {
        Gate::forUser($reviewer)->authorize('payments.review');

        if (! in_array($decision, ['approved', 'rejected'], true)) {
            throw ValidationException::withMessages(['decision' => 'تصمیم بررسی معتبر نیست.']);
        }

        $reason = trim((string) $reason);
        if ($decision === 'rejected' && ($reason === '' || mb_strlen($reason) > 1500)) {
            throw ValidationException::withMessages(['reason' => 'برای رد پرداخت، دلیل معتبر لازم است.']);
        }

        return DB::transaction(function () use ($reviewer, $receiptId, $decision, $reason): PaymentReceipt {
            $target = PaymentReceipt::query()->findOrFail($receiptId);
            $order = Order::query()->whereKey($target->order_id)->lockForUpdate()->firstOrFail();
            $receipt = $order->receipts()->whereKey($receiptId)->lockForUpdate()->firstOrFail();
            $latest = $order->receipts()->max('attempt_number');

            if ($order->order_status !== 'awaiting_payment'
                || $order->payment_status !== 'under_review'
                || $receipt->status !== 'pending'
                || $receipt->attempt_number !== (int) $latest) {
                throw ValidationException::withMessages(['receipt' => 'این رسید دیگر برای بررسی معتبر نیست.']);
            }

            $receipt->update([
                'status' => $decision,
                'rejection_reason' => $decision === 'rejected' ? $reason : null,
                'reviewed_by' => $reviewer->id,
                'reviewed_at' => now(),
            ]);
            $order->update([
                'payment_status' => $decision,
                'order_status' => $decision === 'approved' ? 'confirmed' : 'awaiting_payment',
            ]);

            if ($decision === 'approved') {
                DB::table('order_status_histories')->insert([
                    'order_id' => $order->id,
                    'from_status' => 'awaiting_payment',
                    'to_status' => 'confirmed',
                    'actor_type' => 'admin',
                    'admin_user_id' => $reviewer->id,
                    'created_at' => now(),
                ]);
            }

            $this->events->execute($receipt, $decision === 'approved' ? 'payment.approved' : 'payment.receipt_rejected');

            // Do not copy card information, address, images or rejection text into audit properties.
            activity()->causedBy($reviewer)->performedOn($receipt)
                ->withProperties(['receipt_id' => $receipt->id, 'decision' => $decision])
                ->log('payment.receipt_reviewed');

            return $receipt->refresh();
        }, 3);
    }
}
