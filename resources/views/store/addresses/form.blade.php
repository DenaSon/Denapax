@component('layouts.store', ['title' => $address->exists ? 'ویرایش نشانی' : 'نشانی جدید'])
    <livewire:store.address-editor :address-id="$address->exists ? $address->id : null" :return-to="$returnTo" />
@endcomponent
