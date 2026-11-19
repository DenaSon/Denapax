<?php

namespace App\Application\Actions;

use App\Domain\Shipping\ShippingCalculator;
use App\Models\ShippingSetting;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class EstimateCartShipping
{
    public function __construct(private readonly ShippingCalculator $shipping) {}

    /**
     * Use the same weight-based tariff as checkout, without requiring a delivery
     * address under the current nationwide pricing policy.
     *
     * @param  Collection<int, array{variant: \App\Models\ProductVariant, quantity: int, total_irr: int, available: bool}>  $lines
     * @return array{shipping_fee_irr: int, total_irr: int}|null
     */
    public function execute(Collection $lines): ?array
    {
        if ($lines->isEmpty() || $lines->contains(static fn (array $line): bool => ! $line['available']
            || $line['quantity'] < 1
            || $line['quantity'] > 1000
            || (int) $line['variant']->shipping_weight_grams <= 0)) {
            return null;
        }

        $settings = ShippingSetting::query()->find(1);
        if ($settings === null) {
            return null;
        }

        $itemsWeightGrams = (int) $lines->sum(
            static fn (array $line): int => (int) $line['variant']->shipping_weight_grams * (int) $line['quantity']
        );

        try {
            $feeIrr = $this->shipping->calculate($itemsWeightGrams, $settings->toRate())->feeIrr;
        } catch (InvalidArgumentException) {
            // Never display a false final amount when the tariff is incomplete.
            return null;
        }

        return [
            'shipping_fee_irr' => $feeIrr,
            'total_irr' => (int) $lines->sum('total_irr') + $feeIrr,
        ];
    }
}
