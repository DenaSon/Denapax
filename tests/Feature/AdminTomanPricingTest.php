<?php

namespace Tests\Feature;

use App\Livewire\Admin\Catalog\ProductEditor;
use App\Livewire\Admin\ShippingSettingsPage;
use App\Models\Category;
use App\Models\Product;
use App\Models\ShippingSetting;
use App\Models\User;
use App\Support\Money;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class AdminTomanPricingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin);
    }

    public function test_product_price_is_entered_in_toman_and_saved_exactly_in_rial(): void
    {
        $category = Category::create(['name' => 'Nuts', 'slug' => 'nuts']);
        Livewire::test(ProductEditor::class)
            ->set('categoryId', $category->id)
            ->set('title', 'Dena walnuts')
            ->set('slug', 'dena-walnuts')
            ->set('summary', 'Premium walnuts')
            ->set('isActive', true)
            ->call('addVariant')
            ->set('variants.0.title', 'One kilo')
            ->set('variants.0.price_toman', '۱٬۳۸۹٬۰۰۰')
            ->call('save')
            ->assertHasNoErrors();

        $product = Product::where('slug', 'dena-walnuts')->firstOrFail();
        $variant = $product->variants()->firstOrFail();
        $this->assertSame(13890000, $variant->price_irr);
        $this->assertTrue($variant->is_available);

        Livewire::test(ProductEditor::class, ['product' => $product])
            ->assertSet('variants.0.price_toman', '1389000')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(13890000, $variant->fresh()->price_irr);
    }

    public function test_old_sub_toman_rial_prices_are_not_silently_rounded(): void
    {
        $category = Category::create(['name' => 'Nuts', 'slug' => 'nuts']);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'Old price',
            'slug' => 'old-price',
            'summary' => 'Legacy price',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'sku' => 'LEGACY-1',
            'title' => 'One kilo',
            'sale_quantity' => 1,
            'sale_unit' => 'kg',
            'price_irr' => 13889999,
            'shipping_weight_grams' => 1000,
            'is_available' => true,
        ]);

        $this->assertSame('1,388,999.9', Money::formatToman($variant->price_irr));
        Livewire::test(ProductEditor::class, ['product' => $product])
            ->assertSet('variants.0.price_toman', '1388999.9')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(13889999, $variant->fresh()->price_irr);
    }

    public function test_invalid_price_and_unavailable_only_publication_are_rejected(): void
    {
        $category = Category::create(['name' => 'Nuts', 'slug' => 'nuts']);
        $editor = Livewire::test(ProductEditor::class)
            ->set('categoryId', $category->id)
            ->set('title', 'Invalid walnuts')
            ->set('slug', 'invalid-walnuts')
            ->set('summary', 'Test')
            ->set('isActive', true)
            ->call('addVariant')
            ->set('variants.0.title', 'One kilo');

        $editor->set('variants.0.price_toman', '1389000.12')
            ->call('save')
            ->assertHasErrors(['variants.0.price_toman']);

        $editor->set('variants.0.price_toman', '0')
            ->call('save')
            ->assertHasErrors(['variants']);

        $editor->set('variants.0.price_toman', '1389000')
            ->set('variants.0.is_available', false)
            ->call('save')
            ->assertHasErrors(['variants']);

        $this->assertDatabaseMissing('products', ['slug' => 'invalid-walnuts']);
    }

    public function test_shipping_tariffs_are_edited_in_toman_without_touching_other_rial_values(): void
    {
        $settings = ShippingSetting::create([
            'id' => 1,
            'base_weight_grams' => 1000,
            'base_price_irr' => 999999,
            'extra_step_grams' => 1000,
            'extra_step_price_irr' => 500000,
            'packaging_weight_grams' => 150,
        ]);

        Livewire::test(ShippingSettingsPage::class)
            ->assertSet('basePriceToman', '99999.9')
            ->assertSet('extraStepPriceToman', '50000')
            ->set('basePriceToman', '۱۰۰٬۰۰۰')
            ->call('save')
            ->assertHasNoErrors();

        $this->assertSame(1000000, $settings->fresh()->base_price_irr);
        $this->assertSame(500000, $settings->fresh()->extra_step_price_irr);
    }
}
