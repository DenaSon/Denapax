<?php

namespace App\Livewire\Store;

use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddressPicker extends Component
{
    #[Locked]
    public ?int $selectedAddressId = null;

    public function choose(int $addressId): void
    {
        abort_unless(
            auth('customer')->check() && auth('customer')->user()->addresses()->whereKey($addressId)->exists(),
            404,
        );

        // The existing GET controller recalculates the quote and signed checkout
        // fingerprint. Do not calculate or trust shipping amounts in the browser.
        $this->redirect(route('checkout.index', ['address_id' => $addressId]), navigate: true);
    }

    public function render(): View
    {
        abort_unless(auth('customer')->check(), 403);

        return view('livewire.store.address-picker', [
            'addresses' => auth('customer')->user()->addresses()
                ->orderByDesc('is_default')->orderByDesc('id')->get(),
        ]);
    }
}
