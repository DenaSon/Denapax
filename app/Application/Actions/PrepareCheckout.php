<?php

namespace App\Application\Actions;

use App\Domain\Shipping\ShippingCalculator;
use App\Models\Customer;
use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use Illuminate\Validation\ValidationException;

class PrepareCheckout
{
    public function __construct(private readonly ShippingCalculator $shipping) {}

    public function execute(Customer $customer, int $addressId): array
    {
        $address = $customer->addresses()->find($addressId);

        if (! $address) {
            throw ValidationException::withMessages(['address' => 'نشانی انتخاب‌شده متعلق به شما نیست.']);
        }

        $cart = $customer->cart?->load(['items.variant.product.category', 'items.variant.product']);
        if (! $cart || $cart->items->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'سبد خرید خالی است.']);
        }

        $settings = ShippingSetting::find(1);
        $store = StoreSetting::find(1);

        if (! $settings || ! $store || ! preg_match('/^[0-9]{16}$/', $store->card_number)) {
            throw ValidationException::withMessages(['checkout' => 'تنظیمات ارسال یا کارت فروشگاه هنوز آماده نیست.']);
        }

        $lines = [];
        $goods = 0;
        $itemsWeight = 0;

        foreach ($cart->items as $item) {
            $variant = $item->variant;
            $product = $variant?->product;
            if (! $variant || ! $product || ! $product->is_active || ! $product->category?->is_active
                || ! $variant->is_available || $variant->shipping_weight_grams < 1
                || $item->quantity < 1 || $item->quantity > 1000) {
                throw ValidationException::withMessages(['cart' => 'موجودی یا مشخصات یک کالا تغییر کرده است؛ سبد را بازبینی کنید.']);
            }

            $lineTotal = $variant->price_irr * $item->quantity;
            $goods += $lineTotal;
            $itemsWeight += $variant->shipping_weight_grams * $item->quantity;
            $lines[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant->id,
                'sku_snapshot' => $variant->sku,
                'product_title_snapshot' => $product->title,
                'variant_title_snapshot' => $variant->title,
                'sale_quantity_snapshot' => $variant->sale_quantity,
                'sale_unit_snapshot' => $variant->sale_unit,
                'unit_weight_grams_snapshot' => $variant->shipping_weight_grams,
                'unit_price_irr' => $variant->price_irr,
                'quantity' => $item->quantity,
                'line_total_irr' => $lineTotal,
            ];
        }

        usort($lines, fn (array $a, array $b) => $a['product_variant_id'] <=> $b['product_variant_id']);
        $quote = $this->shipping->calculate($itemsWeight, $settings->toRate());

        return [
            'address' => $address->only([
                'recipient_name', 'recipient_mobile', 'province', 'city', 'address_line', 'postal_code',
            ]),
            'items' => $lines,
            'goods_subtotal_irr' => $goods,
            'shipping_weight_grams' => $quote->totalWeightGrams,
            'shipping_fee_irr' => $quote->feeIrr,
            'total_irr' => $goods + $quote->feeIrr,
            'shipping_rule_snapshot' => $settings->snapshot(),
            'payment_instructions_snapshot' => [
                'card_number' => $store->card_number,
                'card_holder_name' => $store->card_holder_name,
            ],
        ];
    }

    public function fingerprint(array $quote): string
    {
        return hash('sha256', json_encode($quote, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }
}
