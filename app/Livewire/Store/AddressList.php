<?php

namespace App\Livewire\Store;

use App\Application\Actions\SaveCustomerAddress;
use Illuminate\Contracts\View\View;
use Livewire\Component;

class AddressList extends Component
{
    public string $notice = '';

    public function makeDefault(int $addressId): void
    {
        abort_unless(auth('customer')->check(), 403);
        app(SaveCustomerAddress::class)->setDefault(auth('customer')->user(), $addressId);
        $this->notice = 'نشانی پیش‌فرض تغییر کرد.';
    }

    public function delete(int $addressId): void
    {
        abort_unless(auth('customer')->check(), 403);
        app(SaveCustomerAddress::class)->delete(auth('customer')->user(), $addressId);
        $this->notice = 'نشانی حذف شد.';
    }

    public function render(): View
    {
        abort_unless(auth('customer')->check(), 403);

        return view('livewire.store.address-list', [
            'addresses' => auth('customer')->user()->addresses()
                ->orderByDesc('is_default')->orderByDesc('id')->get(),
        ]);
    }
}
