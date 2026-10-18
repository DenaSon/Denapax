<?php

namespace App\Livewire\Admin;

use App\Models\StoreSetting;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class SettingsPage extends Component
{
    public string $storeName = 'دنا';

    public string $supportContact = '';

    public string $cardNumber = '';

    public string $cardHolderName = '';

    public function mount(): void
    {
        Gate::authorize('settings.manage');
        if ($settings = StoreSetting::find(1)) {
            $this->storeName = $settings->store_name;
            $this->supportContact = $settings->support_contact ?? '';
            $this->cardNumber = $settings->card_number;
            $this->cardHolderName = $settings->card_holder_name;
        }
    }

    public function save(): void
    {
        Gate::authorize('settings.manage');
        $data = $this->validate([
            'storeName' => 'required|string|max:160',
            'supportContact' => 'nullable|string|max:160',
            'cardNumber' => ['required', 'string', 'regex:/^\\d{16}$/'],
            'cardHolderName' => 'required|string|max:200',
        ]);
        $settings = StoreSetting::updateOrCreate(['id' => 1], [
            'store_name' => $data['storeName'],
            'support_contact' => $data['supportContact'],
            'card_number' => $data['cardNumber'],
            'card_holder_name' => $data['cardHolderName'],
            'updated_by' => auth()->id(),
        ]);
        activity()->causedBy(auth()->user())->performedOn($settings)
            ->withProperties(['changed' => ['store_name', 'support_contact', 'payment_instructions']])
            ->log('store.settings_updated');
        session()->flash('status', 'تنظیمات ذخیره شد.');
    }

    public function render()
    {
        Gate::authorize('settings.manage');

        return view('livewire.admin.settings-page')->layout('layouts.admin');
    }
}
