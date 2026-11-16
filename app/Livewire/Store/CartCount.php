<?php

namespace App\Livewire\Store;

use App\Application\Actions\StoreCart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class CartCount extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Re-render with the authoritative guest session or customer cart.
    }

    public function render(): View
    {
        return view('livewire.store.cart-count', [
            'quantity' => app(StoreCart::class)->quantity(auth('customer')->user()),
        ]);
    }
}
