<?php

namespace App\Application\Actions;

use App\Integrations\Payments\ZarinpalGateway;
use App\Models\GatewayPaymentAttempt;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class VerifyGatewayPayment
{
    public function __construct(private readonly ZarinpalGateway $gateway) {}

    public function execute(string $authority): ?GatewayPaymentAttempt
    {
        if (! preg_match('/^[A-Za-z0-9]{20,80}$/D', $authority)) {
            return null;
        }

        $claimed = DB::transaction(function () use ($authority): ?GatewayPaymentAttempt {
            $attempt = GatewayPaymentAttempt::query()->where('authority', $authority)->lockForUpdate()->first();

            if (! $attempt) {
                return null;
            }

            if (in_array($attempt->status, ['verified', 'requires_review'], true)
                || ($attempt->status === 'verifying' && $attempt->verification_started_at?->greaterThan(now()->subMinutes(2)))) {
                return $attempt;
            }

            $attempt->update(['status' => 'verifying', 'verification_started_at' => now()]);

            return $attempt;
        }, 3);

        if (! $claimed || in_array($claimed->status, ['verified', 'requires_review'], true)
            || ($claimed->status === 'verifying'
                && $claimed->verification_started_at?->greaterThan(now()->subMinutes(2))
                && $claimed->wasChanged('status') === false)) {
            return $claimed;
        }

        try {
            $reference = $this->gateway->verify($claimed->amount_irr, $authority);
        } catch (Throwable) {
            GatewayPaymentAttempt::query()->whereKey($claimed->id)
                ->where('status', 'verifying')
                ->update(['status' => 'verification_pending', 'last_error' => 'verification_unavailable']);

            return $claimed->fresh();
        }

        return DB::transaction(function () use ($claimed, $reference): GatewayPaymentAttempt {
            $order = Order::query()->whereKey($claimed->order_id)->lockForUpdate()->firstOrFail();
            $attempt = GatewayPaymentAttempt::query()->whereKey($claimed->id)->lockForUpdate()->firstOrFail();

            if (in_array($attempt->status, ['verified', 'requires_review'], true)) {
                return $attempt;
            }

            if ($reference === null) {
                $attempt->update(['status' => 'failed', 'last_error' => 'not_verified']);

                return $attempt->refresh();
            }

            if ($attempt->amount_irr !== $order->total_irr
                || $order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)) {
                $attempt->update([
                    'status' => 'requires_review',
                    'reference_id' => $reference,
                    'verified_at' => now(),
                    'last_error' => 'order_payment_conflict',
                ]);

                return $attempt->refresh();
            }

            $attempt->update([
                'status' => 'verified',
                'reference_id' => $reference,
                'verified_at' => now(),
                'last_error' => null,
            ]);
            $order->update(['payment_status' => 'approved', 'order_status' => 'confirmed']);

            DB::table('order_status_histories')->insert([
                'order_id' => $order->id,
                'from_status' => 'awaiting_payment',
                'to_status' => 'confirmed',
                'actor_type' => 'system',
                'created_at' => now(),
            ]);
            DB::table('outbox_events')->insert([
                'id' => (string) Str::uuid(),
                'aggregate_type' => 'payment_gateway',
                'aggregate_id' => $attempt->id,
                'event_type' => 'payment.approved',
                'payload' => json_encode([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'gateway_payment_attempt_id' => $attempt->id,
                ], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
            ]);

            return $attempt->refresh();
        }, 3);
    }
}
