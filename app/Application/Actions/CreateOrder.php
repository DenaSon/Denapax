<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreateOrder
{
    public function __construct(private readonly PrepareCheckout $checkout) {}

    public function execute(Customer $customer, int $addressId, string $checkoutKey, string $expectedFingerprint): Order
    {
        if (! Str::isUuid($checkoutKey) || ! preg_match('/^[a-f0-9]{64}$/', $expectedFingerprint)) {
            throw ValidationException::withMessages(['checkout' => 'درخواست تأیید سفارش نامعتبر است.']);
        }

        return DB::transaction(function () use ($customer, $addressId, $checkoutKey, $expectedFingerprint): Order {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();

            $existing = Order::query()->where('checkout_key', $checkoutKey)->first();
            if ($existing) {
                if ($existing->customer_id !== $customer->id) {
                    throw ValidationException::withMessages(['checkout' => 'درخواست متعلق به مشتری دیگری است.']);
                }

                return $existing;
            }

            // Current authoritative prices, weights, card instructions and address.
            // The hash prevents confirming totals different from what was displayed.
            $quote = $this->checkout->execute($customer->fresh(), $addressId);
            if (! hash_equals($expectedFingerprint, $this->checkout->fingerprint($quote))) {
                throw ValidationException::withMessages(['checkout' => 'قیمت یا مشخصات سفارش تغییر کرده؛ دوباره پیش‌نمایش بگیرید.']);
            }

            $order = Order::create([
                'order_number' => 'DN'.Str::upper((string) Str::ulid()),
                'checkout_key' => $checkoutKey,
                'customer_id' => $customer->id,
                'order_status' => 'awaiting_payment',
                'payment_status' => 'awaiting_receipt',
                ...$quote['address'],
                'goods_subtotal_irr' => $quote['goods_subtotal_irr'],
                'shipping_fee_irr' => $quote['shipping_fee_irr'],
                'total_irr' => $quote['total_irr'],
                'shipping_weight_grams' => $quote['shipping_weight_grams'],
                'shipping_rule_snapshot' => $quote['shipping_rule_snapshot'],
                'payment_instructions_snapshot' => $quote['payment_instructions_snapshot'],
            ]);

            foreach ($quote['items'] as $line) {
                $order->items()->create($line);
            }

            DB::table('order_status_histories')->insert([
                'order_id' => $order->id,
                'from_status' => null,
                'to_status' => 'awaiting_payment',
                'actor_type' => 'system',
                'created_at' => now(),
            ]);

            DB::table('outbox_events')->insert([
                'id' => (string) Str::uuid(),
                'aggregate_type' => 'order',
                'aggregate_id' => $order->id,
                'event_type' => 'order.created',
                'payload' => json_encode(['order_id' => $order->id, 'customer_id' => $customer->id], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
            ]);

            $customer->cart()->firstOrFail()->items()->delete();

            return $order->load('items');
        }, 3);
    }
}
