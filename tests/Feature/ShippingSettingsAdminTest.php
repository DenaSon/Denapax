<?php

namespace Tests\Feature;

use App\Livewire\Admin\ShippingSettingsPage;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShippingSettingsAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorized_shipping_manager_can_update_tariff(): void
    {
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);

        $operator = User::factory()->create();
        $operator->assignRole('operator');
        $this->actingAs($operator)->get('/admin/shipping')->assertOk();

        Livewire::test(ShippingSettingsPage::class)
            ->set('baseWeightGrams', 1000)
            ->set('basePriceToman', '100000')
            ->set('extraStepGrams', 1000)
            ->set('extraStepPriceToman', '50000')
            ->set('packagingWeightGrams', 150)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('shipping_settings', [
            'id' => 1, 'packaging_weight_grams' => 150, 'extra_step_price_irr' => 500000,
        ]);
    }
}
