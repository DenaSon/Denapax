<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Order extends Model
{
    protected $fillable = [
        'order_number', 'checkout_key', 'customer_id', 'order_status', 'payment_status',
        'recipient_name', 'recipient_mobile', 'province', 'city', 'address_line',
        'postal_code', 'goods_subtotal_irr', 'shipping_fee_irr', 'total_irr',
        'shipping_weight_grams', 'shipping_rule_snapshot', 'payment_instructions_snapshot',
    ];

    protected function casts(): array
    {
        return [
            'goods_subtotal_irr' => 'integer',
            'shipping_fee_irr' => 'integer',
            'total_irr' => 'integer',
            'shipping_weight_grams' => 'integer',
            'shipping_rule_snapshot' => 'array',
            'payment_instructions_snapshot' => 'array',
        ];
    }

    public function shipment(): \Illuminate\Database\Eloquent\Relations\HasOne
    {
        return $this->hasOne(Shipment::class);
    }

    public function receipts(): HasMany
    {
        return $this->hasMany(PaymentReceipt::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }
}
