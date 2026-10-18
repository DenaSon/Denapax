<?php

namespace App\Application\Actions;

use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransitionOrder
{
    public function execute(User $staff, int $orderId, string $action, ?string $carrier = null, ?string $tracking = null): Order
    {
        abort_unless($staff->is_active, 403);
        Gate::forUser($staff)->authorize(in_array($action, ['ship', 'deliver'], true)
            ? 'shipping.manage' : 'orders.update');

        if (! in_array($action, ['prepare', 'ship', 'deliver', 'cancel'], true)) {
            throw ValidationException::withMessages(['order' => 'عملیات وضعیت سفارش نامعتبر است.']);
        }

        $carrier = trim((string) $carrier);
        $tracking = trim((string) $tracking);
        if ($action === 'ship' && ($carrier === '' || $tracking === ''
            || mb_strlen($carrier) > 80 || mb_strlen($tracking) > 120)) {
            throw ValidationException::withMessages(['tracking' => 'نام شرکت حمل و کد رهگیری معتبر الزامی هستند.']);
        }

        return DB::transaction(function () use ($staff, $orderId, $action, $carrier, $tracking): Order {
            $order = Order::query()->whereKey($orderId)->lockForUpdate()->firstOrFail();
            $old = $order->order_status;
            $to = match ($action) {
                'prepare' => 'preparing',
                'ship' => 'shipped',
                'deliver' => 'delivered',
                'cancel' => 'canceled',
            };
            $valid = match ($action) {
                'prepare' => $old === 'confirmed' && $order->payment_status === 'approved',
                'ship' => $old === 'preparing' && $order->payment_status === 'approved',
                'deliver' => $old === 'shipped' && $order->shipment()->exists(),
                // A pending receipt may already represent a bank transfer; review it first.
                'cancel' => $old === 'awaiting_payment'
                    && in_array($order->payment_status, ['awaiting_receipt', 'rejected'], true),
            };

            if (! $valid) {
                throw ValidationException::withMessages(['order' => 'این تغییر وضعیت برای سفارش مجاز نیست.']);
            }

            if ($action === 'ship') {
                $order->shipment()->create([
                    'carrier' => $carrier,
                    'tracking_code' => $tracking,
                    'shipped_at' => now(),
                    'recorded_by' => $staff->id,
                ]);
            } elseif ($action === 'deliver') {
                $order->shipment()->update(['delivered_at' => now()]);
            }

            $order->update(['order_status' => $to]);
            DB::table('order_status_histories')->insert([
                'order_id' => $order->id,
                'from_status' => $old,
                'to_status' => $to,
                'actor_type' => 'admin',
                'admin_user_id' => $staff->id,
                'created_at' => now(),
            ]);

            DB::table('outbox_events')->insert([
                'id' => (string) Str::uuid(),
                'aggregate_type' => 'order',
                'aggregate_id' => $order->id,
                'event_type' => 'order.'.$to,
                'payload' => json_encode([
                    'order_id' => $order->id,
                    'customer_id' => $order->customer_id,
                ], JSON_THROW_ON_ERROR),
                'occurred_at' => now(),
            ]);

            activity()->causedBy($staff)->performedOn($order)
                ->withProperties(['from' => $old, 'to' => $to])
                ->log('order.status_changed');

            return $order->refresh();
        }, 3);
    }
}
