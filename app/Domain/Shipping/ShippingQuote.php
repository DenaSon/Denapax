<?php

namespace App\Domain\Shipping;

/**
 * Immutable shipping quote to be snapshotted when checkout creates an order.
 */
final readonly class ShippingQuote
{
    public function __construct(
        public int $totalWeightGrams,
        public int $additionalSteps,
        public int $feeIrr,
    ) {}
}
