<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\StoreSetting;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PhaseOneFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_public_registration_is_unavailable_and_inactive_staff_cannot_log_in(): void
    {
        $this->get('/register')->assertNotFound();
        $this->post('/register')->assertNotFound();

        $user = User::factory()->create([
            'email' => 'inactive@example.test',
            'password' => 'my-long-secret',
            'is_active' => false,
        ]);
        $user->assignRole('super-admin');

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'my-long-secret',
        ])->assertSessionHasErrors();

        $this->assertGuest();
    }

    public function test_only_authorized_staff_can_edit_store_payment_settings(): void
    {
        $operator = User::factory()->create();
        $operator->assignRole('operator');

        $this->actingAs($operator)->get('/admin/settings')->assertForbidden();
    }

    public function test_authorized_admin_can_edit_store_payment_settings(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');

        $this->assertTrue($admin->is_active);
        $this->assertTrue($admin->can('settings.manage'));
        $this->actingAs($admin)->get('/admin/settings')->assertOk();
        Livewire::test(\App\Livewire\Admin\SettingsPage::class)
            ->set('storeName', 'Dena')
            ->set('supportContact', '@dena_support')
            ->set('cardNumber', '6037991234567890')
            ->set('cardHolderName', 'Store Owner')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame('Dena', StoreSetting::findOrFail(1)->store_name);
        $this->assertSame('6037991234567890', StoreSetting::findOrFail(1)->card_number);
    }

    public function test_product_variants_have_independent_prices_and_shipping_grams(): void
    {
        $category = Category::create(['name' => 'Dried fruit', 'slug' => 'dried-fruit']);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);

        Livewire::test(\App\Livewire\Admin\Catalog\ProductEditor::class)
            ->set('categoryId', $category->id)
            ->set('title', 'Dena figs')
            ->set('slug', 'dena-figs')
            ->set('summary', 'Dried figs')
            ->set('description', 'From Dena')
            ->set('isActive', true)
            ->set('features', [['name' => 'Origin', 'value' => 'Dena']])
            ->set('variants', [
                [
                    'id' => null, 'sku' => 'FIG-500', 'title' => '500 g',
                    'sale_quantity' => '500', 'sale_unit' => 'g',
                    'price_irr' => 1200000, 'shipping_weight_grams' => 550,
                    'is_available' => true,
                ],
                [
                    'id' => null, 'sku' => 'FIG-1000', 'title' => '1 kg',
                    'sale_quantity' => '1', 'sale_unit' => 'kg',
                    'price_irr' => 2300000, 'shipping_weight_grams' => 1100,
                    'is_available' => true,
                ],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('slug', 'dena-figs')->firstOrFail();
        $this->assertCount(1, $product->features);
        $this->assertSame(2, $product->variants()->count());
        $this->assertSame(1200000, ProductVariant::where('sku', 'FIG-500')->firstOrFail()->price_irr);
        $this->assertSame(1100, ProductVariant::where('sku', 'FIG-1000')->firstOrFail()->shipping_weight_grams);
    }

    public function test_unprivileged_user_cannot_edit_catalog(): void
    {
        $staff = User::factory()->create();
        $this->actingAs($staff)->get('/admin/products')->assertForbidden();
    }
}
