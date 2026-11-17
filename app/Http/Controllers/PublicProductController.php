<?php

namespace App\Http\Controllers;

use App\Application\Actions\StoreCart;
use App\Integrations\Payments\ZarinpalGateway;
use App\Models\Product;
use App\Models\StoreSetting;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class PublicProductController extends Controller
{
    public function __invoke(Request $request, ZarinpalGateway $gateway, StoreCart $cart, string $slug): View
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
        $photos = $product->getMedia('gallery')
            ->take(8)
            ->map(fn ($media): array => ['url' => $media->getUrl(), 'full_url' => $media->getFullUrl()])
            ->concat(
                $product->variants
                    ->map(fn ($variant) => $variant->getFirstMedia('variant-image'))
                    ->filter()
                    ->map(fn ($media): array => ['url' => $media->getUrl(), 'full_url' => $media->getFullUrl()])
            )
            ->unique('url')
            ->values();

        $variantPhotoIndexes = $product->variants->mapWithKeys(function ($variant) use ($photos): array {
            $url = $variant->getFirstMedia('variant-image')?->getUrl();

            if ($url === null) {
                return [];
            }

            $index = $photos->search(fn (array $photo): bool => $photo['url'] === $url);

            return $index === false ? [] : [$variant->id => $index];
        })->all();

        $canonicalUrl = route('products.show', ['slug' => $product->slug]);
        $description = (string) ($product->summary ?: $product->description ?: $product->title);
        $metaDescription = mb_substr(trim(strip_tags($description)), 0, 155);
        $minimumPrice = (int) $product->variants->min('price_irr');
        $variantPurchaseData = $product->variants->map(fn ($variant): array => [
            'id' => (int) $variant->id,
            'title' => $variant->title,
            'priceLabel' => Money::formatToman((int) $variant->price_irr),
            'priceToman' => Money::tomanInput((int) $variant->price_irr),
        ])->values()->all();
        $cartQuantities = $cart->lines($request->user('customer'))
            ->filter(fn (array $line): bool => (int) $line['variant']->product_id === (int) $product->id)
            ->mapWithKeys(fn (array $line): array => [(string) $line['variant']->id => (int) $line['quantity']])
            ->all();
        $storeSetting = StoreSetting::query()->find(1);
        $manualAvailable = preg_match('/^[0-9]{16}$/D', (string) ($storeSetting?->card_number ?? '')) === 1;

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
            'minimumPrice', 'schema', 'variantPhotoIndexes', 'variantPurchaseData'
        ) + [
            'cartQuantities' => (object) $cartQuantities,
            'gatewayEnabled' => $gateway->enabled(),
            'manualAvailable' => $manualAvailable,
        ]);
    }
}
