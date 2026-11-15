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

            $latest = $order->gatewayAttempts()->latest('id')->first();

            if ($latest && in_array($latest->status, ['initializing', 'redirected', 'verifying', 'verification_pending'], true)
                && $latest->created_at->greaterThan(now()->subMinutes(45))) {
                if ($latest->status === 'redirected' && $latest->authority) {
                    return $latest;
                }

                throw ValidationException::withMessages(['payment' => 'درخواست پرداخت در حال بررسی است. کمی بعد وضعیت سفارش را بررسی کنید.']);
            }

            if ($latest && in_array($latest->status, ['initializing', 'redirected', 'verifying', 'verification_pending'], true)) {
                $latest->update(['status' => 'expired']);
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
            $attempt->update(['status' => 'failed', 'last_error' => 'request_failed']);

            throw ValidationException::withMessages(['payment' => 'درگاه پرداخت در دسترس نیست. دوباره تلاش کنید.']);
        }

        DB::transaction(function () use ($attempt, $authority): void {
            $order = Order::query()->whereKey($attempt->order_id)->lockForUpdate()->firstOrFail();
            $locked = GatewayPaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
                $locked->update(['status' => 'expired']);

                throw ValidationException::withMessages(['payment' => 'وضعیت سفارش تغییر کرده است.']);
            }

            $locked->update(['status' => 'redirected', 'authority' => $authority]);
        }, 3);

        return $this->gateway->paymentUrl($authority);
    }
}
