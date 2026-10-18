<?php

namespace Tests\Unit\Domain;

use App\Domain\Shipping\ShippingCalculator;
use App\Domain\Shipping\ShippingRate;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShippingCalculatorTest extends TestCase
{
    #[DataProvider('weightCases')]
    public function test_shipping_tiers_are_inclusive_at_the_upper_boundary(
        int $grams,
        int $expectedSteps,
        int $expectedFeeIrr,
    ): void {
        $rate = new ShippingRate(1000, 1_000_000, 1000, 500_000, 0);
        $quote = (new ShippingCalculator)->calculate($grams, $rate);

        $this->assertSame($grams, $quote->totalWeightGrams);
        $this->assertSame($expectedSteps, $quote->additionalSteps);
        $this->assertSame($expectedFeeIrr, $quote->feeIrr);
    }

    public static function weightCases(): array
    {
        return [
            '250g' => [250, 0, 1_000_000],
            '999g' => [999, 0, 1_000_000],
            '1000g' => [1000, 0, 1_000_000],
            '1001g' => [1001, 1, 1_500_000],
            '1500g' => [1500, 1, 1_500_000],
            '2000g' => [2000, 1, 1_500_000],
            '2001g' => [2001, 2, 2_000_000],
        ];
    }

    public function test_packaging_weight_can_move_an_order_to_the_next_tier(): void
    {
        $rate = new ShippingRate(1000, 1_000_000, 1000, 500_000, 150);
        $quote = (new ShippingCalculator)->calculate(900, $rate);

        $this->assertSame(1050, $quote->totalWeightGrams);
        $this->assertSame(1, $quote->additionalSteps);
        $this->assertSame(1_500_000, $quote->feeIrr);
    }

    public function test_invalid_rates_are_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ShippingRate(1000, 1_000_000, 0, 500_000, 0);
    }

    public function test_empty_cart_weight_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new ShippingCalculator)->calculate(0, new ShippingRate(1000, 1_000_000, 1000, 500_000, 0));
    }
}
