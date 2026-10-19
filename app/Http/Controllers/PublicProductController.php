<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\PublicStorefront;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function __invoke(Request $request, string $slug): View
    {
        $product = Product::query()
            ->publiclyOrderable()
            ->where('slug', $slug)
            ->with([
                'category',
                'features',
                'media',
                'variants' => fn ($query) => $query
                    ->where('is_available', true)
                    ->where('price_irr', '>', 0)
                    ->orderBy('sort_order'),
                'variants.media',
            ])
            ->firstOrFail();

        $photos = $product->getMedia('gallery')
            ->map(fn ($media): array => ['url' => $media->getUrl(), 'full_url' => $media->getFullUrl()])
            ->concat(
                $product->variants
                    ->map(fn ($variant) => $variant->getFirstMedia('variant-image'))
                    ->filter()
                    ->map(fn ($media): array => ['url' => $media->getUrl(), 'full_url' => $media->getFullUrl()])
            )
            ->unique('url')
            ->take(8)
            ->values();

        $canonicalUrl = route('products.show', ['slug' => $product->slug]);
        $description = (string) ($product->summary ?: $product->description ?: $product->title);
        $metaDescription = mb_substr(trim(strip_tags($description)), 0, 155);
        $telegramUrl = PublicStorefront::telegramUrl();
        $minimumPrice = (int) $product->variants->min('price_irr');

        // Structured-data offers are priced in actual DB currency (IRR), not
        // display currency (toman). No fake reviews, inventory quantities or ratings.
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Product',
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
                'seller' => ['@type' => 'Organization', 'name' => 'DenaPax'],
            ])->all(),
        ];

        if ($photos->isNotEmpty()) {
            $schema['image'] = $photos->pluck('full_url')->all();
        }

        return view('landing.product', compact(
            'product', 'photos', 'canonicalUrl', 'metaDescription',
            'minimumPrice', 'telegramUrl', 'schema'
        ));
    }
}
