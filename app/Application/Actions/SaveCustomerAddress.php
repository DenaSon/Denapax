<?php

namespace App\Application\Actions;

use App\Models\Customer;
use App\Models\CustomerAddress;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SaveCustomerAddress
{
    public function execute(Customer $customer, array $attributes, ?int $addressId = null): CustomerAddress
    {
        $data = Validator::make($attributes, [
            'recipient_name' => 'required|string|max:220',
            'recipient_mobile' => ['required', 'string', 'regex:/^\\+?[0-9]{10,15}$/'],
            'province' => 'required|string|max:100',
            'city' => 'required|string|max:100',
            'address_line' => 'required|string|min:8|max:2000',
            'postal_code' => ['required', 'string', 'regex:/^[0-9]{10}$/'],
            'label' => 'nullable|string|max:80',
        ])->validate();

        return DB::transaction(function () use ($customer, $data, $addressId): CustomerAddress {
            // Serialize default-address selection for this customer.
            Customer::query()->lockForUpdate()->findOrFail($customer->id);

            $address = $addressId === null
                ? new CustomerAddress(['customer_id' => $customer->id])
                : $customer->addresses()->findOrFail($addressId);

            if ($address->exists === false) {
                $address->is_default = $customer->addresses()->doesntExist();
            }

            $address->fill($data)->save();

            return $address->refresh();
        });
    }

    public function setDefault(Customer $customer, int $addressId): void
    {
        DB::transaction(function () use ($customer, $addressId): void {
            Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $address = $customer->addresses()->findOrFail($addressId);
            $customer->addresses()->where('id', '!=', $address->id)->update(['is_default' => false]);
            $address->update(['is_default' => true]);
        });
    }

    public function delete(Customer $customer, int $addressId): void
    {
        DB::transaction(function () use ($customer, $addressId): void {
            Customer::query()->lockForUpdate()->findOrFail($customer->id);
            $address = $customer->addresses()->findOrFail($addressId);
            $wasDefault = $address->is_default;
            $address->delete();

            if ($wasDefault) {
                $next = $customer->addresses()->orderBy('id')->first();
                if ($next) {
                    $next->update(['is_default' => true]);
                }
            }
        });
    }
}
