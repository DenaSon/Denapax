<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\ProductImageResolver;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedProductImagesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_sibling_photo_is_reused_in_cart_catalog_product_gallery_and_admin_without_copying_media(): void
    {
        [$product, $missing] = $this->product('shared-image');
        $withImage = $this->variant($product, 'shared-image-1kg', 1000, 5);
        $siblingMedia = $withImage->addMedia(UploadedFile::fake()->image('one-kilo.jpg'))
            ->toMediaCollection('variant-image', 'public');

        $this->post(route('cart.add'), ['variant_id' => $missing->id, 'quantity' => 1]);
        $thumb = $siblingMedia->hasGeneratedConversion('product-thumb')
            ? $siblingMedia->getUrl('product-thumb') : $siblingMedia->getUrl();
        $square = $siblingMedia->hasGeneratedConversion('product-square')
            ? $siblingMedia->getUrl('product-square') : $siblingMedia->getUrl();

        $this->get(route('cart.index'))->assertOk()
            ->assertSee('src="'.$thumb.'"', false)
            ->assertSee('alt="'.$product->title.'"', false)
            ->assertSee('store-cart-item-size', false)
            ->assertSee('500 گرم')
            ->assertSee('ارسال بر اساس وزن سبد محاسبه شده', false) // Only when shipping settings exist.
            ->assertDontSee('بدون تصویر');

        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertViewHas('variantPhotoIndexes', fn (array $indexes): bool => $indexes[$missing->id] === 0
                && $indexes[$withImage->id] === 0)
            ->assertSee('data-variant-photo-index="0"', false);

        $this->get(route('home'))->assertOk()
            ->assertSee('src="'.$square.'"', false);

        $this->seed(RolesAndPermissionsSeeder::class);
        $admin = User::factory()->create();
        $admin->assignRole('super-admin');
        $this->actingAs($admin, 'web')
            ->get(route('admin.products.edit', ['product' => $product->id]))
            ->assertOk()
            ->assertSee('تصویر مشترک محصول (بدون نیاز به آپلود مجدد)')
            ->assertSee('تصویر اختصاصی این تنوع');

        $this->assertDatabaseCount('media', 1);
    }

    public function test_gallery_is_preferred_over_sibling_but_dedicated_variant_photo_still_wins(): void
    {
        [$product, $target] = $this->product('priority');
        $sibling = $this->variant($product, 'priority-1kg', 1000, 10);

        $siblingMedia = $sibling->addMedia(UploadedFile::fake()->image('sibling.jpg'))
            ->toMediaCollection('variant-image', 'public');

        $resolver = app(ProductImageResolver::class);
        $this->assertSame($siblingMedia->id, $resolver->forVariant($target, $product)?->id);

        $gallery = $product->addMedia(UploadedFile::fake()->image('gallery.jpg'))
            ->toMediaCollection('gallery', 'public');
        $this->assertSame($gallery->id, $resolver->forVariant($target, $product)?->id);
        $this->assertSame($gallery->id, $resolver->forProduct($product)?->id);

        $dedicated = $target->addMedia(UploadedFile::fake()->image('five-hundred.jpg'))
            ->toMediaCollection('variant-image', 'public');
        $this->assertSame($dedicated->id, $resolver->forVariant($target, $product)?->id);

        $this->get(route('products.show', $product->slug))->assertOk()
            ->assertViewHas('variantPhotoIndexes', fn (array $indexes): bool => $indexes[$target->id] === 2
                && $indexes[$sibling->id] === 1);
        $this->assertDatabaseCount('media', 3);
    }

    public function test_sibling_selection_is_deterministic_and_does_not_cross_products(): void
    {
        [$product, $missing] = $this->product('deterministic');
        $later = $this->variant($product, 'later', 1000, 20);
        $earlier = $this->variant($product, 'earlier', 250, 1);
        $laterImage = $later->addMedia(UploadedFile::fake()->image('later.jpg'))
            ->toMediaCollection('variant-image', 'public');
        $earlierImage = $earlier->addMedia(UploadedFile::fake()->image('earlier.jpg'))
            ->toMediaCollection('variant-image', 'public');

        [$otherProduct, $otherMissing] = $this->product('unrelated');
        $resolver = app(ProductImageResolver::class);

        $product->load('media', 'variants.media');
        $this->assertSame($earlierImage->id, $resolver->forVariant($missing, $product)?->id);
        $this->assertNotSame($laterImage->id, $resolver->forProduct($product)?->id);
        $this->assertNull($resolver->forVariant($otherMissing, $otherProduct));

        $this->post(route('cart.add'), ['variant_id' => $otherMissing->id, 'quantity' => 1]);
        $this->get(route('cart.index'))->assertOk()
            ->assertSee('بدون تصویر');
    }

    private function product(string $slug): array
    {
        $category = Category::create([
            'name' => 'خشکبار '.$slug,
            'slug' => 'image-'.$slug,
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'خشکبار دنا '.$slug,
            'slug' => 'shared-'.$slug,
            'summary' => 'محصولی از دنا',
            'is_active' => true,
        ]);

        return [$product, $this->variant($product, $slug.'-500g', 500, 0)];
    }

    private function variant(Product $product, string $sku, int $grams, int $position): ProductVariant
    {
        return $product->variants()->create([
            'sku' => 'IMG-'.$sku,
            'title' => 'بسته '.$grams.' گرمی',
            'sale_quantity' => $grams,
            'sale_unit' => 'g',
            'price_irr' => 5000000,
            'shipping_weight_grams' => $grams + 50,
            'is_available' => true,
            'sort_order' => $position,
        ]);
    }
}
