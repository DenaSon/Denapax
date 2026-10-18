<?php

namespace App\Application\Actions;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ManageCart
{
    public function cart(Customer $customer): Cart
    {
        return $customer->cart()->firstOrCreate([]);
    }

    public function add(Customer $customer, int $variantId, int $quantity = 1): void
    {
        $this->checkQuantity($quantity);

        DB::transaction(function () use ($customer, $variantId, $quantity): void {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $variant = ProductVariant::query()->whereKey($variantId)
                ->where('is_available', true)
                ->whereHas('product', fn ($q) => $q->published())
                ->first();

            if (! $variant || $variant->shipping_weight_grams <= 0) {
                throw ValidationException::withMessages(['cart' => 'این تنوع اکنون قابل خرید نیست.']);
            }

            $cart = $this->cart($customer);
            $line = $cart->items()->where('product_variant_id', $variantId)->first();
            $newQuantity = ($line?->quantity ?? 0) + $quantity;
            $this->checkQuantity($newQuantity);

            $cart->items()->updateOrCreate(
                ['product_variant_id' => $variantId],
                ['quantity' => $newQuantity],
            );
        });
    }

    public function setQuantity(Customer $customer, int $variantId, int $quantity): void
    {
        if ($quantity < 0) {
            throw ValidationException::withMessages(['quantity' => 'تعداد نمی‌تواند منفی باشد.']);
        }

        if ($quantity > 0) {
            $this->checkQuantity($quantity);
        }

        DB::transaction(function () use ($customer, $variantId, $quantity): void {
            Customer::query()->whereKey($customer->id)->lockForUpdate()->firstOrFail();
            $line = $this->cart($customer)->items()->where('product_variant_id', $variantId)->first();

            if (! $line) {
                throw ValidationException::withMessages(['cart' => 'این کالا در سبد شما نیست.']);
            }

            if ($quantity === 0) {
                $line->delete();
            } else {
                $line->update(['quantity' => $quantity]);
            }
        });
    }

    public function checkQuantity(int $quantity): void
    {
        if ($quantity < 1 || $quantity > 1000) {
            throw ValidationException::withMessages(['quantity' => 'تعداد باید بین ۱ تا ۱۰۰۰ باشد.']);
        }
    }
}
