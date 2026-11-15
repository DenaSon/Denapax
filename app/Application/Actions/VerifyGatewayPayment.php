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

        // Do not hold a database lock across a network call. A fencing token
        // ensures a timed-out verifier cannot override a later authoritative one.
        [$attempt, $token] = DB::transaction(function () use ($authority): array {
            $attempt = GatewayPaymentAttempt::query()
                ->where('authority', $authority)->lockForUpdate()->first();

            if (! $attempt || in_array($attempt->status, ['verified', 'requires_review'], true)) {
                return [$attempt, null];
            }

            if ($attempt->status === 'verifying'
                && $attempt->verification_started_at?->greaterThan(now()->subMinutes(2))) {
                return [$attempt, null];
            }

            $token = (string) Str::uuid();
            $attempt->update([
                'status' => 'verifying',
                'verification_started_at' => now(),
                'verification_token' => $token,
                'last_error' => null,
            ]);

            return [$attempt, $token];
        }, 3);

        if ($attempt === null || $token === null) {
            return $attempt?->fresh();
        }

        try {
            $reference = $this->gateway->verify($attempt->amount_irr, $authority);
        } catch (Throwable) {
            GatewayPaymentAttempt::query()
                ->whereKey($attempt->id)
                ->where('verification_token', $token)
                ->where('status', 'verifying')
                ->update([
                    'status' => 'verification_pending',
                    'last_error' => 'verification_unavailable',
                    'verification_token' => null,
                ]);

            return $attempt->fresh();
        }

        return DB::transaction(function () use ($attempt, $token, $reference): GatewayPaymentAttempt {
            $order = Order::query()->whereKey($attempt->order_id)->lockForUpdate()->firstOrFail();
            $locked = GatewayPaymentAttempt::query()->whereKey($attempt->id)->lockForUpdate()->firstOrFail();

            if ($locked->status !== 'verifying' || $locked->verification_token !== $token) {
                return $locked;
            }

            if ($reference === null) {
                // Generic non-100/101 provider codes cannot prove that a bank
                // did not collect money. An operator must reconcile the outcome.
                $locked->update([
                    'status' => 'verification_pending',
                    'last_error' => 'provider_not_confirmed',
                    'verification_token' => null,
                ]);

                return $locked->refresh();
            }

            // Another charge/receipt, even one opened after an operator cleared
            // this Authority, requires explicit reconciliation instead of
            // automatically marking the order paid a second time.
            $competing = $order->gatewayAttempts()
                ->where('id', '!=', $locked->id)
                ->where('status', '!=', 'cleared_unpaid')
                ->exists();
            $pendingReceipt = $order->receipts()->where('status', 'pending')->exists();

            if ($locked->amount_irr !== $order->total_irr
                || $order->order_status !== 'awaiting_payment'
                || ! in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true)
                || $competing || $pendingReceipt) {
                $locked->update([
                    'status' => 'requires_review',
                    'reference_id' => $reference,
                    'verified_at' => now(),
                    'verification_token' => null,
                    'last_error' => 'settled_payment_conflict',
                ]);

                return $locked->refresh();
            }

            $locked->update([
                'status' => 'verified',
                'reference_id' => $reference,
                'verified_at' => now(),
                'verification_token' => null,
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
                'aggregate_id' => $locked->id,
                'event_type' => 'payment.approved',
                'payload' => json_encode([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                    'gateway_payment_attempt_id' => $locked->id,
                ], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
            ]);

            return $locked->refresh();
        }, 3);
    }
}
