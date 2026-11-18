<?php

namespace App\Livewire\Store;

use App\Application\Actions\StoreCart;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

class LandingHeaderCart extends Component
{
    #[On('cart-updated')]
    public function refreshCount(): void
    {
        // Re-render from the authoritative cart after mutations.
    }

    public function render(): View
    {
        return view('livewire.store.landing-header-cart', [
            'quantity' => app(StoreCart::class)->quantity(auth('customer')->user()),
        ]);
    }
}
