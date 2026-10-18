<?php

namespace App\Models;

use App\Domain\Shipping\ShippingRate;
use Illuminate\Database\Eloquent\Model;

class ShippingSetting extends Model
{
    protected $fillable = [
        'base_weight_grams', 'base_price_irr', 'extra_step_grams',
        'extra_step_price_irr', 'packaging_weight_grams', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'base_weight_grams' => 'integer',
            'base_price_irr' => 'integer',
            'extra_step_grams' => 'integer',
            'extra_step_price_irr' => 'integer',
            'packaging_weight_grams' => 'integer',
        ];
    }

    public function toRate(): ShippingRate
    {
        return new ShippingRate(
            baseWeightGrams: $this->base_weight_grams,
            basePriceIrr: $this->base_price_irr,
            additionalStepWeightGrams: $this->extra_step_grams,
            additionalStepPriceIrr: $this->extra_step_price_irr,
            packagingWeightGrams: $this->packaging_weight_grams,
        );
    }

    public function snapshot(): array
    {
        return $this->only([
            'base_weight_grams', 'base_price_irr', 'extra_step_grams',
            'extra_step_price_irr', 'packaging_weight_grams',
        ]);
    }
}
