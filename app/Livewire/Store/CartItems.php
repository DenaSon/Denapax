<?php

namespace App\Livewire\Store;

use App\Application\Actions\StoreCart;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class CartItems extends Component
{
    public function changeQuantity(int $variantId, int $quantity): void
    {
        app(StoreCart::class)->setQuantity(auth('customer')->user(), $variantId, $quantity);
        $this->dispatch('cart-updated');
    }

    public function remove(int $variantId): void
    {
        $this->changeQuantity($variantId, 0);
    }

    public function render(): View
    {
        $lines = app(StoreCart::class)->lines(auth('customer')->user());

        return view('livewire.store.cart-items', [
            'lines' => $lines,
            'subtotal' => (int) $lines->sum('total_irr'),
        ]);
    }
}
