<?php

namespace Tests\Feature;

use App\Application\Actions\BrowseCatalog;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ShippingSetting;
use App\Models\StoreSetting;
use Database\Seeders\DatabaseSeeder;
use Database\Seeders\DemoProductsSeeder;
use Database\Seeders\InitialSettingsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class InitialSeedersTest extends TestCase
{
    use RefreshDatabase;

    public function test_default_seed_creates_safe_settings_without_products_or_fake_payment_instructions(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DatabaseSeeder::class);

        $this->assertDatabaseCount('store_settings', 1);
        $this->assertDatabaseCount('shipping_settings', 1);
        $this->assertDatabaseCount('products', 0);

        $store = StoreSetting::findOrFail(1);
        $this->assertSame('دنا', $store->store_name);
        $this->assertSame('', $store->card_number);
        $this->assertSame('', $store->card_holder_name);
        $this->assertNull($store->support_contact);

        $shipping = ShippingSetting::findOrFail(1);
        $this->assertSame(1000, $shipping->base_weight_grams);
        $this->assertSame(0, $shipping->base_price_irr);
        $this->assertSame(0, $shipping->extra_step_price_irr);
    }

    public function test_settings_seed_never_overwrites_values_edited_by_staff(): void
    {
        StoreSetting::create([
            'id' => 1,
            'store_name' => 'فروشگاه سفارشی',
            'support_contact' => '@valid_shop',
            'card_number' => '6037991234567890',
            'card_holder_name' => 'Store Owner',
        ]);
        ShippingSetting::create([
            'id' => 1,
            'base_weight_grams' => 1500,
            'base_price_irr' => 1500000,
            'extra_step_grams' => 500,
            'extra_step_price_irr' => 750000,
            'packaging_weight_grams' => 120,
        ]);

        $this->seed(InitialSettingsSeeder::class);

        $this->assertSame('فروشگاه سفارشی', StoreSetting::findOrFail(1)->store_name);
        $this->assertSame('6037991234567890', StoreSetting::findOrFail(1)->card_number);
        $this->assertSame(750000, ShippingSetting::findOrFail(1)->extra_step_price_irr);
        $this->assertSame(120, ShippingSetting::findOrFail(1)->packaging_weight_grams);
    }

    public function test_demo_seeder_creates_exactly_three_inactive_idempotent_products_only_when_requested(): void
    {
        $this->seed(DatabaseSeeder::class);
        $this->seed(DemoProductsSeeder::class);
        $this->seed(DemoProductsSeeder::class);

        $this->assertDatabaseCount('categories', 1);
        $this->assertDatabaseCount('products', 3);
        $this->assertDatabaseCount('product_variants', 3);
        $this->assertSame(0, Product::query()->where('is_active', true)->count());
        $this->assertSame(0, ProductVariant::query()->where('is_available', true)->count());
        $this->assertSame(0, Category::query()->where('is_active', true)->count());
        $this->assertSame(0, app(BrowseCatalog::class)->products()->total());
        $this->assertEqualsCanonicalizing(
            ['denapax-demo-dried-figs', 'denapax-demo-raisins', 'denapax-demo-walnuts'],
            Product::query()->pluck('slug')->all(),
        );

        $fig = ProductVariant::where('sku', 'DENAPAX-DEMO-FIG-500')->firstOrFail();
        $this->assertSame(1950000, $fig->price_irr);
        $this->assertSame(550, $fig->shipping_weight_grams);

        $fig->update(['price_irr' => 999000]);
        $this->seed(DemoProductsSeeder::class);
        $this->assertSame(999000, $fig->fresh()->price_irr);
        $this->assertDatabaseCount('products', 3);
    }

    public function test_demo_seeder_rejects_production_before_writing_data(): void
    {
        $previous = app()->environment();
        app()->detectEnvironment(fn () => 'production');

        try {
            $this->seed(DemoProductsSeeder::class);
            $this->fail('Demo seeding must be blocked in production.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('restricted', $exception->getMessage());
            $this->assertDatabaseCount('products', 0);
        } finally {
            app()->detectEnvironment(fn () => $previous);
        }
    }
}
