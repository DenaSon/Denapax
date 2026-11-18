<?php

namespace App\Http\Controllers;

use App\Application\Actions\StoreCart;
use App\Models\Product;
use App\Support\Money;
use App\Support\StoreSeo;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function __invoke(Request $request, StoreCart $cart, string $slug): View
    {
        $product = Product::query()
            ->publiclyOrderable()
            ->where('slug', $slug)
            ->with([
                'category',
                'features',
                'media',
                'variants' => fn ($query) => $query
                    ->availableForSale()
                    ->orderBy('sort_order'),
                'variants.media',
            ])
            ->firstOrFail();

        // Keep the product's first gallery photo as the cover, but include
        // every sellable variant's own image so choosing a package can always
        // select its matching photograph. Thumbnail images load lazily.
        $photoFor = static function ($media, string $alt): array {
            $displayUrl = $media->hasGeneratedConversion('product-square')
                ? $media->getUrl('product-square')
                : $media->getUrl();

            return [
                'media_id' => (int) $media->id,
                'alt' => $alt,
                'url' => $displayUrl,
                'thumb_url' => $media->hasGeneratedConversion('product-thumb')
                    ? $media->getUrl('product-thumb')
                    : $displayUrl,
                'full_url' => $media->getFullUrl(),
            ];
        };

        $photos = $product->getMedia('gallery')
            ->take(8)
            ->values()
            ->map(fn ($media, $index) => $photoFor($media, $product->title.' - تصویر '.($index + 1)))
            ->concat(
                $product->variants
                    ->map(function ($variant) use ($photoFor, $product): ?array {
                        $media = $variant->getFirstMedia('variant-image');

                        return $media ? $photoFor($media, $product->title.' - '.$variant->title) : null;
                    })
                    ->filter()
            )
            ->unique('media_id')
            ->values();

        $variantPhotoIndexes = $product->variants->mapWithKeys(function ($variant) use ($photos): array {
            $mediaId = $variant->getFirstMedia('variant-image')?->id;

            if ($mediaId === null) {
                return [];
            }

            $index = $photos->search(fn (array $photo): bool => $photo['media_id'] === (int) $mediaId);

            return $index === false ? [] : [$variant->id => $index];
        })->all();

        $canonicalUrl = route('products.show', ['slug' => $product->slug]);
        $description = trim(strip_tags((string) ($product->summary ?: $product->description ?: $product->title)));
        $metaDescription = mb_substr(
            $description.' | مشخصات و قیمت بسته‌های موجود را در دناپکس ببینید.',
            0,
            155,
        );
        $seoTitle = 'خرید '.$product->title.' | دناپکس';
        $breadcrumbSchema = StoreSeo::breadcrumbs([
            ['name' => 'خانه', 'url' => route('home')],
            ['name' => $product->category->name, 'url' => route('categories.show', ['slug' => $product->category->slug])],
            ['name' => $product->title, 'url' => $canonicalUrl],
        ]);
        $variantPurchaseData = $product->variants->map(fn ($variant): array => [
            'id' => (int) $variant->id,
            'title' => $variant->title,
            'priceLabel' => Money::formatToman((int) $variant->price_irr),
            'priceToman' => Money::tomanInput((int) $variant->price_irr),
        ])->values()->all();
        $cartQuantities = $cart->quantitiesForVariants(
            $request->user('customer'),
            $product->variants->modelKeys(),
        );
        // Structured-data offers are priced in actual DB currency (IRR), not
        // display currency (toman). No fake reviews, inventory quantities or ratings.
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            '@id' => $canonicalUrl.'#product',
            'name' => $product->title,
            'description' => $description,
            'category' => $product->category->name,
            'url' => $canonicalUrl,
            'offers' => $product->variants->map(fn ($variant): array => [
                '@type' => 'Offer',
                'url' => $canonicalUrl,
                'sku' => $variant->sku,
                'name' => $variant->title,
                'price' => (string) $variant->price_irr,
                'priceCurrency' => 'IRR',
                'availability' => 'https://schema.org/InStock',
                'seller' => ['@id' => route('home').'#organization', '@type' => 'OnlineStore', 'name' => 'دناپکس'],
            ])->all(),
        ];

        if ($photos->isNotEmpty()) {
            $schema['image'] = $photos->pluck('full_url')->all();
        }

        return view('landing.product', compact(
            'product', 'photos', 'canonicalUrl', 'metaDescription',
            'schema', 'seoTitle', 'breadcrumbSchema', 'variantPhotoIndexes', 'variantPurchaseData'
        ) + [
            'cartQuantities' => (object) $cartQuantities,
        ]);
    }
}
