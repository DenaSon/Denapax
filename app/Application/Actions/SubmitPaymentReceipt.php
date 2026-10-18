<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\Order;
use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class SubmitPaymentReceipt
{
    public function __construct(private readonly RecordPaymentEvent $events) {}

    public function execute(Customer $customer, int $orderId, string $storagePath, ?string $fileId = null): PaymentReceipt
    {
        if (! preg_match('~^payment-receipts/[a-f0-9-]{36}\.(jpg|png|webp)$~', $storagePath)
            || ! Storage::disk('receipts')->exists($storagePath)) {
            throw ValidationException::withMessages(['receipt' => 'فایل رسید معتبر نیست.']);
        }

        return DB::transaction(function () use ($customer, $orderId, $storagePath, $fileId): PaymentReceipt {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->first();

            if (! $order || $order->customer_id !== $customer->id) {
                throw ValidationException::withMessages(['receipt' => 'سفارش متعلق به شما نیست.']);
            }

            if ($order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)
                || $order->receipts()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['receipt' => 'در حال حاضر امکان ارسال رسید برای این سفارش وجود ندارد.']);
            }

            $attempt = ((int) $order->receipts()->max('attempt_number')) + 1;

            $receipt = $order->receipts()->create([
                'attempt_number' => $attempt,
                'storage_path' => $storagePath,
                'telegram_file_id' => $fileId,
                'status' => 'pending',
                'submitted_at' => now(),
            ]);

            $order->update(['payment_status' => 'under_review']);
            $this->events->execute($receipt, 'payment.receipt_submitted');

            return $receipt;
        }, 3);
    }
}
