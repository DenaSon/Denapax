<?php

namespace Tests\Feature;

use App\Integrations\Telegram\CatalogMessages;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Mockery;
use SergiX44\Nutgram\Nutgram;
use SergiX44\Nutgram\Telegram\Types\Internal\InputFile;
use Tests\TestCase;

class TelegramProductMediaDeliveryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'app.url' => 'https://store.example.test',
            'filesystems.disks.public.url' => 'https://store.example.test/storage',
        ]);
        Storage::fake('public');
    }

    public function test_variant_photo_tries_public_https_url_after_local_file_upload_fails(): void
    {
        $variant = $this->publishedVariantWithPhoto();
        $attempts = [];

        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendPhoto')->twice()
            ->andReturnUsing(function (...$args) use (&$attempts) {
                $attempts[] = $args[0];
                if (count($attempts) === 1) {
                    throw new \RuntimeException('simulated local file upload failure');
                }

                return null;
            });
        $bot->shouldNotReceive('sendMessage');

        app(CatalogMessages::class)->variantImage($bot, $variant->id);

        $this->assertCount(2, $attempts);
        $this->assertInstanceOf(InputFile::class, $attempts[0]);
        $this->assertIsString($attempts[1]);
        $this->assertStringStartsWith('https://store.example.test/storage/', $attempts[1]);
    }

    public function test_unavailable_photo_falls_back_to_one_message_after_both_strategies_fail(): void
    {
        $variant = $this->publishedVariantWithPhoto();
        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendPhoto')->twice()->andThrow(new \RuntimeException('provider rejects photo'));
        $bot->shouldReceive('sendMessage')->once()->with('تصویر موقتاً قابل نمایش نیست.')->andReturnNull();

        app(CatalogMessages::class)->variantImage($bot, $variant->id);
    }

    public function test_short_product_detail_does_not_retry_both_photo_strategies_twice(): void
    {
        $variant = $this->publishedVariantWithPhoto();
        $bot = Mockery::mock(Nutgram::class);
        $bot->shouldReceive('sendPhoto')->twice()->andThrow(new \RuntimeException('photo unavailable'));
        $bot->shouldReceive('sendMessage')->once()->andReturnNull();

        app(CatalogMessages::class)->details($bot, $variant->product_id);
    }

    private function publishedVariantWithPhoto(): ProductVariant
    {
        $category = Category::create([
            'name' => 'Dry fruits',
            'slug' => 'test-photo-category',
            'is_active' => true,
        ]);
        $product = Product::create([
            'category_id' => $category->id,
            'title' => 'آلبالو طبیعی دنا',
            'slug' => 'cherry-for-photo-test',
            'summary' => 'طبیعی',
            'description' => '',
            'is_active' => true,
        ]);
        $variant = $product->variants()->create([
            'sku' => 'photo-variant',
            'title' => 'یک کیلو',
            'sale_quantity' => 1,
            'sale_unit' => 'kg',
            'price_irr' => 3500000,
            'shipping_weight_grams' => 1000,
            'is_available' => true,
        ]);

        $variant->addMediaFromString(base64_decode(
            'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Y9FZtgAAAAASUVORK5CYII=',
        ))->usingFileName('cherry.png')->toMediaCollection('variant-image', 'public');

        return $variant;
    }
}
