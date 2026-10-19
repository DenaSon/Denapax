<?php

namespace App\Livewire\Admin;

use App\Models\ShippingSetting;
use App\Support\Money;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;

class ShippingSettingsPage extends Component
{
    public int $baseWeightGrams = 1000;

    public string $basePriceToman = '0';

    public int $extraStepGrams = 1000;

    public string $extraStepPriceToman = '0';

    public int $packagingWeightGrams = 0;

    public function mount(): void
    {
        Gate::authorize('shipping.manage');

        if ($settings = ShippingSetting::find(1)) {
            $this->baseWeightGrams = $settings->base_weight_grams;
            $this->basePriceToman = Money::tomanInput($settings->base_price_irr);
            $this->extraStepGrams = $settings->extra_step_grams;
            $this->extraStepPriceToman = Money::tomanInput($settings->extra_step_price_irr);
            $this->packagingWeightGrams = $settings->packaging_weight_grams;
        }
    }

    public function save(): void
    {
        Gate::authorize('shipping.manage');

        $this->basePriceToman = Money::normalizeTomanInput($this->basePriceToman);
        $this->extraStepPriceToman = Money::normalizeTomanInput($this->extraStepPriceToman);

        $data = $this->validate([
            'baseWeightGrams' => 'required|integer|min:1',
            'basePriceToman' => ['required', 'string', 'max:16', 'regex:/^(0|[1-9][0-9]*)(?:\.[0-9])?$/D'],
            'extraStepGrams' => 'required|integer|min:1',
            'extraStepPriceToman' => ['required', 'string', 'max:16', 'regex:/^(0|[1-9][0-9]*)(?:\.[0-9])?$/D'],
            'packagingWeightGrams' => 'required|integer|min:0',
        ]);

        ShippingSetting::updateOrCreate(['id' => 1], [
            'base_weight_grams' => $data['baseWeightGrams'],
            'base_price_irr' => Money::tomanToIrr($data['basePriceToman']),
            'extra_step_grams' => $data['extraStepGrams'],
            'extra_step_price_irr' => Money::tomanToIrr($data['extraStepPriceToman']),
            'packaging_weight_grams' => $data['packagingWeightGrams'],
            'updated_by' => auth()->id(),
        ]);

        activity()->causedBy(auth()->user())->withProperties(['settings' => 'shipping'])->log('shipping.settings_updated');
        session()->flash('status', 'تعرفه پستی ذخیره شد.');
    }

    public function render()
    {
        Gate::authorize('shipping.manage');

        return view('livewire.admin.shipping-settings-page')->layout('layouts.admin');
    }
}
