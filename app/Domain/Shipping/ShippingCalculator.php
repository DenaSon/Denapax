<?php

namespace App\Domain\Shipping;

use InvalidArgumentException;

/**
 * Calculates the one-parcel nationwide shipping fee without floating-point math.
 */
final class ShippingCalculator
{
    public function calculate(int $itemsWeightGrams, ShippingRate $rate): ShippingQuote
    {
        if ($itemsWeightGrams <= 0) {
            throw new InvalidArgumentException('Items weight must be positive.');
        }

        $totalWeightGrams = $itemsWeightGrams + $rate->packagingWeightGrams;
        $excessGrams = max(0, $totalWeightGrams - $rate->baseWeightGrams);

        $additionalSteps = $excessGrams === 0
            ? 0
            : intdiv($excessGrams - 1, $rate->additionalStepWeightGrams) + 1;

        $feeIrr = $rate->basePriceIrr + $additionalSteps * $rate->additionalStepPriceIrr;

        return new ShippingQuote(
            totalWeightGrams: $totalWeightGrams,
            additionalSteps: $additionalSteps,
            feeIrr: $feeIrr,
        );
    }
}
