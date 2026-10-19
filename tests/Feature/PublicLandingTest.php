<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicLandingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
    }

    public function test_guest_sees_only_published_orderable_products_and_exact_toman_prices(): void
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'nuts', 'is_active' => true]);
        $visible = $this->product($category, 'گردو طبیعی', 'walnuts', 13890000);
        $visible->variants()->create([
            'sku' => 'WALNUT-UNAVAILABLE', 'title' => 'موجود نیست',
            'sale_quantity' => 1, 'sale_unit' => 'kg', 'price_irr' => 100,
            'shipping_weight_grams' => 1000, 'is_available' => false,
        ]);
        $this->product($category, 'محصول مخفی', 'hidden', 400000)->update(['is_active' => false]);
        $this->product($category, 'محصول تمام‌شده', 'sold-out', 200000)->variants()->update(['is_available' => false]);
        $inactive = Category::create(['name' => 'دسته غیرفعال', 'slug' => 'inactive', 'is_active' => false]);
        $this->product($inactive, 'محصول دسته غیرفعال', 'inactive-item', 900000);

        $this->get('/')
            ->assertOk()->assertSee('گردو طبیعی')->assertSee('1,389,000')
            ->assertDontSee('محصول مخفی')->assertDontSee('محصول تمام‌شده')
            ->assertDontSee('محصول دسته غیرفعال')->assertDontSee('موجود نیست');
    }

    public function test_category_filter_and_pagination_are_public(): void
    {
        $nuts = Category::create(['name' => 'مغزها', 'slug' => 'nuts', 'is_active' => true]);
        $fruit = Category::create(['name' => 'میوه خشک', 'slug' => 'fruit', 'is_active' => true]);
        for ($index = 1; $index <= 13; $index++) {
            $this->product($nuts, 'گردو شماره '.$index, 'nuts-'.$index, 120000);
        }
        $this->product($fruit, 'آلو بخارا', 'plum', 180000);

        $this->get('/?category=fruit')->assertOk()->assertSee('آلو بخارا')->assertDontSee('گردو شماره 13');
        $this->get('/?category=nuts&page=2')->assertOk()->assertSee('گردو شماره 1')->assertDontSee('آلو بخارا');
        $this->get('/?category=unknown')->assertOk()->assertSee('آلو بخارا');
    }

    public function test_telegram_button_only_links_to_a_configured_valid_handle(): void
    {
        config()->set('services.telegram.bot_username', 'DenaPaxBot');
        $this->get('/')->assertOk()->assertSee('https://t.me/DenaPaxBot');

        config()->set('services.telegram.bot_username', 'bad/bot');
        $this->get('/')->assertOk()->assertDontSee('https://t.me/bad/bot')
            ->assertSee('لینک بات در حال آماده‌سازی است');
    }

    private function product(Category $category, string $title, string $slug, int $price): Product
    {
        $product = Product::create([
            'category_id' => $category->id, 'title' => $title, 'slug' => $slug,
            'summary' => 'محصولی از دنا', 'description' => 'توضیح نمونه',
            'is_active' => true,
        ]);
        $product->variants()->create([
            'sku' => $slug.'-500', 'title' => 'بسته نیم کیلویی',
            'sale_quantity' => 0.5, 'sale_unit' => 'kg',
            'price_irr' => $price, 'shipping_weight_grams' => 550,
            'is_available' => true,
        ]);

        return $product;
    }
}
