<?php

namespace App\Application\Actions;

use App\Integrations\Payments\ZarinpalGateway;
use App\Models\Customer;
use App\Models\GatewayPaymentAttempt;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class StartGatewayPayment
{
    public function __construct(private readonly ZarinpalGateway $gateway) {}

    public function execute(Customer $customer, int $orderId): string
    {
        if (! $this->gateway->enabled()) {
            throw ValidationException::withMessages(['payment' => 'پرداخت آنلاین هنوز فعال نشده است.']);
        }

        $attempt = DB::transaction(function () use ($customer, $orderId): GatewayPaymentAttempt {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->first();

            if (! $order || $order->customer_id !== $customer->id) {
                throw ValidationException::withMessages(['payment' => 'سفارش معتبر نیست.']);
            }

            if ($order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)
                || $order->receipts()->where('status', 'pending')->exists()) {
                throw ValidationException::withMessages(['payment' => 'این سفارش اکنون قابل پرداخت آنلاین نیست.']);
            }

            // A timeout or an expired session must NEVER automatically authorize
            // another charge. Only an auditable, bank-reconciled clearance does.
            $unsettled = $order->gatewayAttempts()
                ->where('status', '!=', 'cleared_unpaid')
                ->latest('id')
                ->first();

            if ($unsettled) {
                if ($unsettled->status === 'redirected'
                    && $unsettled->authority
                    && $unsettled->created_at->greaterThan(now()->subMinutes(45))) {
                    return $unsettled;
                }

                throw ValidationException::withMessages([
                    'payment' => 'نتیجه پرداخت قبلی هنوز قطعی نیست. برای جلوگیری از پرداخت تکراری، ابتدا وضعیت آن باید بررسی شود.',
                ]);
            }

            return $order->gatewayAttempts()->create([
                'provider' => 'zarinpal',
                'amount_irr' => $order->total_irr,
                'status' => 'initializing',
            ]);
        }, 3);

        if ($attempt->status === 'redirected') {
            return $this->gateway->paymentUrl($attempt->authority);
        }

        try {
            $authority = $this->gateway->request(
                $attempt->amount_irr,
                route('payments.zarinpal.callback'),
                $attempt->order->public_reference,
            );
        } catch (Throwable) {
            // A request may have succeeded at the provider before our timeout.
            // Do not expose a new payment button until an operator reconciles it.
            GatewayPaymentAttempt::query()->whereKey($attempt->id)
                ->where('status', 'initializing')
                ->update(['status' => 'request_unknown', 'last_error' => 'request_outcome_unknown']);

            throw ValidationException::withMessages([
                'payment' => 'پاسخ درگاه دریافت نشد. برای جلوگیری از پرداخت تکراری، وضعیت تراکنش بررسی می‌شود.',
            ]);
        }

        DB::transaction(function () use ($attempt, $authority): void {
            $order = Order::query()->whereKey($attempt->order_id)->lockForUpdate()->firstOrFail();
            $locked = GatewayPaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)
                || $locked->status !== 'initializing') {
                $locked->update(['status' => 'requires_review', 'last_error' => 'request_order_state_conflict']);

                throw ValidationException::withMessages(['payment' => 'وضعیت سفارش تغییر کرده و نیاز به بررسی دارد.']);
            }

            $locked->update(['status' => 'redirected', 'authority' => $authority]);
        }, 3);

        return $this->gateway->paymentUrl($authority);
    }
}
