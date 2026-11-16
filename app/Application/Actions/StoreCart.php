<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class StoreCart
{
    private const SESSION_KEY = 'denapax_cart';

    public function __construct(private readonly ManageCart $carts) {}

    public function add(?Customer $customer, int $variantId, int $quantity): void
    {
        $this->carts->checkQuantity($quantity);
        $variant = ProductVariant::query()
            ->whereKey($variantId)
            ->where('is_available', true)
            ->where('price_irr', '>', 0)
            ->where('shipping_weight_grams', '>', 0)
            ->whereHas('product', fn ($query) => $query->publiclyOrderable())
            ->first();

        if (! $variant) {
            throw ValidationException::withMessages(['cart' => 'این تنوع اکنون قابل خرید نیست.']);
        }

        if ($customer) {
            $this->carts->add($customer, $variantId, $quantity);

            return;
        }

        $items = session()->get(self::SESSION_KEY, []);
        $newQuantity = ((int) ($items[$variantId] ?? 0)) + $quantity;
        $this->carts->checkQuantity($newQuantity);
        $items[$variantId] = $newQuantity;
        session()->put(self::SESSION_KEY, $items);
    }

    public function setQuantity(?Customer $customer, int $variantId, int $quantity): void
    {
        if ($quantity < 0 || $quantity > 1000) {
            throw ValidationException::withMessages(['quantity' => 'تعداد نامعتبر است.']);
        }

        if ($customer) {
            $this->carts->setQuantity($customer, $variantId, $quantity);

            return;
        }

        $items = session()->get(self::SESSION_KEY, []);
        if (! isset($items[$variantId])) {
            throw ValidationException::withMessages(['cart' => 'این کالا در سبد شما نیست.']);
        }

        if ($quantity === 0) {
            unset($items[$variantId]);
        } else {
            $items[$variantId] = $quantity;
        }

        session()->put(self::SESSION_KEY, $items);
    }

    public function lines(?Customer $customer): Collection
    {
        $items = $customer
            ? $this->carts->cart($customer)->items()->pluck('quantity', 'product_variant_id')->all()
            : session()->get(self::SESSION_KEY, []);

        $variants = ProductVariant::query()->with(['product.category', 'product.media', 'media'])
            ->whereIn('id', array_keys($items))->get()->keyBy('id');

        return collect($items)->map(function ($quantity, $variantId) use ($variants): ?array {
            $variant = $variants->get((int) $variantId);

            if (! $variant || ! $variant->product) {
                return null;
            }

            return [
                'variant' => $variant,
                'quantity' => (int) $quantity,
                'total_irr' => (int) $variant->price_irr * (int) $quantity,
                'available' => $variant->is_available && $variant->price_irr > 0
                    && $variant->shipping_weight_grams > 0 && $variant->product->is_active
                    && $variant->product->category?->is_active,
            ];
        })->filter()->values();
    }

    /**
     * Total units in the current guest or signed-in customer's cart.
     * This read must not create an empty persistent cart for every page view.
     */
    public function quantity(?Customer $customer): int
    {
        if ($customer) {
            $cart = $customer->cart()->first();

            return $cart ? (int) $cart->items()->sum('quantity') : 0;
        }

        $items = session()->get(self::SESSION_KEY, []);

        return is_array($items)
            ? array_sum(array_map(static fn ($quantity): int => max(0, (int) $quantity), $items))
            : 0;
    }

    public function mergeGuestCart(Customer $customer): void
    {
        $items = session()->get(self::SESSION_KEY, []);

        foreach ($items as $variantId => $quantity) {
            try {
                $this->add($customer, (int) $variantId, (int) $quantity);
            } catch (ValidationException) {
                // Products may become unavailable between guest browsing and login.
            }
        }

        session()->forget(self::SESSION_KEY);
    }
}
