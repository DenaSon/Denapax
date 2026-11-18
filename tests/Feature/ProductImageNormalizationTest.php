<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Support\ProductImageNormalizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageNormalizationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
        Storage::fake('public');
    }

    public function test_white_margin_detection_is_conservative_and_leaves_original_intact(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'denapax-image-');
        $this->assertNotFalse($path);

        $image = imagecreatetruecolor(320, 280);
        $white = imagecolorallocate($image, 255, 255, 255);
        $walnut = imagecolorallocate($image, 145, 89, 41);
        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 92, 72, 227, 207, $walnut);
        imagepng($image, $path);
        imagedestroy($image);
        $before = hash_file('sha256', $path);

        try {
            $normalizer = app(ProductImageNormalizer::class);
            $manipulations = $normalizer->manipulationsFor($path);
            $this->assertSame($manipulations['product-square'], $manipulations['product-thumb']);

            [$width, $height, $x, $y] = $manipulations['product-square']['manualCrop'];
            $this->assertGreaterThan(0, $x);
            $this->assertGreaterThan(0, $y);
            $this->assertLessThan(320, $width);
            $this->assertLessThan(280, $height);
            $this->assertGreaterThanOrEqual(227, $x + $width);
            $this->assertGreaterThanOrEqual(207, $y + $height);
            $this->assertSame($before, hash_file('sha256', $path));

            $colored = imagecolorallocate($image = imagecreatetruecolor(320, 280), 66, 102, 80);
            imagefill($image, 0, 0, $colored);
            imagepng($image, $path);
            imagedestroy($image);
            $this->assertSame([], $normalizer->manipulationsFor($path));
        } finally {
            @unlink($path);
        }
    }

    public function test_product_and_variant_media_get_square_derivatives_with_originals_preserved(): void
    {
        $product = $this->product();

        $gallery = $product->addMedia(UploadedFile::fake()->image('gallery.png', 360, 220))
            ->toMediaCollection('gallery', 'public');
        $variant = $product->variants()->create([
            'sku' => 'IMG-ONE', 'title' => 'یک کیلویی',
            'sale_quantity' => 1, 'sale_unit' => 'kg',
            'price_irr' => 10000000, 'shipping_weight_grams' => 1020,
            'is_available' => true,
        ]);
        $variantImage = $variant->addMedia(UploadedFile::fake()->image('variant.jpg', 240, 350))
            ->toMediaCollection('variant-image', 'public');

        foreach ([$gallery, $variantImage] as $media) {
            $media->refresh();
            $this->assertTrue($media->hasGeneratedConversion('product-square'));
            $this->assertTrue($media->hasGeneratedConversion('product-thumb'));
            $this->assertFileExists($media->getPath());
            $this->assertSame([960, 960], array_slice(getimagesize($media->getPath('product-square')), 0, 2));
            $this->assertSame([192, 192], array_slice(getimagesize($media->getPath('product-thumb')), 0, 2));
            $this->assertStringEndsWith('.webp', $media->getPath('product-square'));
        }

        $response = $this->get(route('products.show', $product->slug))
            ->assertOk()
            ->assertViewHas('variantPhotoIndexes', fn (array $indexes): bool => $indexes[$variant->id] === 1);
        $response->assertSee($gallery->getUrl('product-square'), false)
            ->assertSee($variantImage->getUrl('product-square'), false)
            ->assertSee($gallery->getUrl('product-thumb'), false)
            ->assertSee($gallery->getFullUrl(), false);

        // Homepage cards use the normalized cover too, without changing the original.
        $this->get('/')->assertOk()->assertSee($gallery->getUrl('product-square'), false);
    }

    public function test_backfill_command_is_dry_run_safe_and_regenerates_product_images_only(): void
    {
        $product = $this->product();
        $image = imagecreatetruecolor(340, 240);
        $white = imagecolorallocate($image, 255, 255, 255);
        $brown = imagecolorallocate($image, 142, 85, 33);
        imagefill($image, 0, 0, $white);
        imagefilledrectangle($image, 90, 60, 245, 185, $brown);
        $source = tempnam(sys_get_temp_dir(), 'denapax-media-');
        $this->assertNotFalse($source);
        imagepng($image, $source);
        imagedestroy($image);

        try {
            $media = $product->addMedia($source)
                ->usingFileName('legacy.png')
                ->toMediaCollection('gallery', 'public');
            $originalHash = hash_file('sha256', $media->getPath());

            $this->artisan('denapax:products:normalize-images', ['--dry-run' => true])
                ->assertSuccessful();
            $this->assertSame([], $media->fresh()->manipulations);

            $this->artisan('denapax:products:normalize-images', ['--id' => (string) $media->id])
                ->assertSuccessful();
            $updated = $media->fresh();
            $this->assertArrayHasKey('manualCrop', $updated->manipulations['product-square']);
            $this->assertTrue($updated->hasGeneratedConversion('product-square'));
            $this->assertTrue($updated->hasGeneratedConversion('product-thumb'));
            $this->assertSame($originalHash, hash_file('sha256', $updated->getPath()));

            // The trimmed walnut should now occupy much more of the square,
            // rather than retain the original embedded white bands.
            $preview = imagecreatefromwebp($updated->getPath('product-square'));
            $pixel = imagecolorsforindex($preview, imagecolorat($preview, 160, 480));
            $this->assertLessThan(220, $pixel['red']);
            imagedestroy($preview);
        } finally {
            @unlink($source);
        }
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'خشکبار', 'slug' => 'images', 'is_active' => true]);

        return Product::create([
            'category_id' => $category->id,
            'title' => 'گردوی دنا',
            'slug' => 'normalized-walnut',
            'summary' => 'گردوی طبیعی دنا',
            'is_active' => true,
        ]);
    }
}
