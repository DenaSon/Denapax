<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id', 'product_variant_id', 'sku_snapshot',
        'product_title_snapshot', 'variant_title_snapshot', 'sale_quantity_snapshot',
        'sale_unit_snapshot', 'unit_weight_grams_snapshot', 'unit_price_irr',
        'quantity', 'line_total_irr',
    ];

    protected function casts(): array
    {
        return [
            'sale_quantity_snapshot' => 'decimal:3',
            'unit_weight_grams_snapshot' => 'integer',
            'unit_price_irr' => 'integer',
            'quantity' => 'integer',
            'line_total_irr' => 'integer',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
