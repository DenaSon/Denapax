<?php

namespace App\Livewire\Store;

use App\Application\Actions\SaveCustomerAddress;
use App\Support\CustomerAddressInput;
use App\Support\IranProvinces;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class AddressEditor extends Component
{
    #[Locked]
    public ?int $addressId = null;

    #[Locked]
    public string $returnTo = 'addresses';

    public string $label = '';

    public string $recipient_name = '';

    public string $recipient_mobile = '';

    public string $province = '';

    public string $city = '';

    public string $address_line = '';

    public string $postal_code = '';

    public function mount(?int $addressId = null, string $returnTo = 'addresses'): void
    {
        abort_unless(auth('customer')->check(), 403);
        $this->addressId = $addressId;
        $this->returnTo = $returnTo === 'checkout' ? 'checkout' : 'addresses';

        if ($addressId === null) {
            $this->recipient_mobile = auth('customer')->user()->mobile;

            return;
        }

        $address = auth('customer')->user()->addresses()->findOrFail($addressId);
        foreach (['label', 'recipient_name', 'recipient_mobile', 'province', 'city', 'address_line', 'postal_code'] as $field) {
            $this->{$field} = (string) ($address->{$field} ?? '');
        }

        // Recognize legacy spacing/Arabic-letter variants without modifying
        // the persisted address before an explicit save.
        $this->province = IranProvinces::normalize($this->province);
    }

    public function save(): void
    {
        abort_unless(auth('customer')->check(), 403);
        $this->province = IranProvinces::normalize($this->province);
        $attributes = CustomerAddressInput::normalize(
            $this->validate(CustomerAddressInput::rules(), CustomerAddressInput::messages()),
        );
        $saved = app(SaveCustomerAddress::class)->execute(
            auth('customer')->user(),
            $attributes,
            $this->addressId,
        );

        session()->flash('status', $this->addressId === null ? 'نشانی تحویل ثبت شد.' : 'نشانی تحویل به‌روزرسانی شد.');

        $this->redirect(
            $this->returnTo === 'checkout'
                ? route('checkout.index', ['address_id' => $saved->id])
                : route('addresses.index'),
            navigate: true,
        );
    }

    public function render(): View
    {
        abort_unless(auth('customer')->check(), 403);

        return view('livewire.store.address-editor', [
            'provinces' => IranProvinces::all(),
        ]);
    }
}
