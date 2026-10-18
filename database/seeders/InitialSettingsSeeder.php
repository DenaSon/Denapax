<?php

namespace Database\Seeders;

use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use Illuminate\Database\Seeder;

class InitialSettingsSeeder extends Seeder
{
    public function run(): void
    {
        // Safe placeholders only. Never invent a valid payment card or contact.
        // Existing settings configured by staff must not be overwritten.
        StoreSetting::firstOrCreate(
            ['id' => 1],
            [
                'store_name' => 'دنا',
                'support_contact' => null,
                'card_number' => '',
                'card_holder_name' => '',
            ],
        );

        // Zero fees are NOT a production tariff. Staff must configure the
        // actual amounts at /admin/shipping before accepting orders.
        ShippingSetting::firstOrCreate(
            ['id' => 1],
            [
                'base_weight_grams' => 1000,
                'base_price_irr' => 0,
                'extra_step_grams' => 1000,
                'extra_step_price_irr' => 0,
                'packaging_weight_grams' => 0,
            ],
        );
    }
}
