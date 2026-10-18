<?php

namespace App\Domain\Shipping;

use InvalidArgumentException;

/**
 * Nationwide flat-zone shipping tariff; all monetary values are integer IRR.
 */
final readonly class ShippingRate
{
    public function __construct(
        public int $baseWeightGrams,
        public int $basePriceIrr,
        public int $additionalStepWeightGrams,
        public int $additionalStepPriceIrr,
        public int $packagingWeightGrams,
    ) {
        if ($baseWeightGrams <= 0 || $additionalStepWeightGrams <= 0) {
            throw new InvalidArgumentException('Weight tier sizes must be positive.');
        }

        if ($basePriceIrr < 0 || $additionalStepPriceIrr < 0 || $packagingWeightGrams < 0) {
            throw new InvalidArgumentException('Prices and packaging weight cannot be negative.');
        }
    }
}
