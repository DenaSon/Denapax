<?php

namespace App\Application\Actions;

use App\Models\PaymentReceipt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RecordPaymentEvent
{
    public function execute(PaymentReceipt $receipt, string $event): void
    {
        DB::table('outbox_events')->insert([
            'id' => (string) Str::uuid(),
            // Receipt-level aggregate keeps one event of each kind per attempt.
            'aggregate_type' => 'payment_receipt',
            'aggregate_id' => $receipt->id,
            'event_type' => $event,
            'payload' => json_encode([
                'order_id' => $receipt->order_id,
                'receipt_id' => $receipt->id,
                'customer_id' => $receipt->order->customer_id,
            ], JSON_THROW_ON_ERROR),
            'occurred_at' => now(),
        ]);
    }
}
