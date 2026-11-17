<?php

namespace App\Livewire\Store;

use App\Application\Actions\StoreCart;
use Illuminate\Contracts\View\View;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Livewire\Component;

class AddToCart extends Component
{
    #[On('cart-add-requested')]
    public function add(int $variantId, int $quantity): void
    {
        try {
            app(StoreCart::class)->add(auth('customer')->user(), $variantId, $quantity);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?: 'امکان افزودن این کالا به سبد خرید وجود ندارد.';

            $this->dispatch('cart-add-failed', message: $message);

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('cart-added', variantId: $variantId, quantity: $quantity);
    }

    #[On('cart-quantity-requested')]
    public function setQuantity(int $variantId, int $quantity): void
    {
        try {
            app(StoreCart::class)->setQuantity(auth('customer')->user(), $variantId, $quantity);
        } catch (ValidationException $exception) {
            $message = collect($exception->errors())->flatten()->first()
                ?: 'امکان به‌روزرسانی تعداد این کالا وجود ندارد.';

            $this->dispatch('cart-add-failed', message: $message);

            return;
        }

        $this->dispatch('cart-updated');
        $this->dispatch('cart-quantity-updated', variantId: $variantId, quantity: $quantity);
    }

    public function render(): View
    {
        return view('livewire.store.add-to-cart');
    }
}
