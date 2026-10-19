<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\Money;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicLandingController extends Controller
{
    public function __invoke(Request $request): View
    {
        $orderable = static fn ($query) => $query
            ->where('is_available', true)
            ->where('price_irr', '>', 0);

        // Only catalog is dynamic. Never expose unpublished or unsellable items.
        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $query) => $query
                ->where('is_active', true)
                ->whereHas('variants', $orderable))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $requestedCategory = $request->query('category');
        $selectedCategory = is_string($requestedCategory)
            && mb_strlen($requestedCategory) <= 80
            && $categories->contains('slug', $requestedCategory)
                ? $requestedCategory
                : null;

        $products = Product::query()
            ->published()
            ->whereHas('variants', $orderable)
            ->when($selectedCategory !== null, fn (Builder $query) => $query
                ->whereHas('category', fn (Builder $category) => $category->where('slug', $selectedCategory)))
            ->with([
                'category',
                'features',
                'media',
                'variants' => fn ($query) => $orderable($query)->orderBy('sort_order'),
                'variants.media',
            ])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        // Public handle only; do not guess the bot name or reveal bot tokens.
        $username = ltrim(trim((string) config('services.telegram.bot_username', '')), '@');
        $telegramUrl = preg_match('/^[a-zA-Z][a-zA-Z0-9_]{4,31}$/D', $username)
            ? 'https://t.me/'.$username
            : null;

        // The preview is limited to the current page and contains only public,
        // already-orderable catalog fields; no extra HTTP endpoint or N+1 lookups.
        $productPreviews = $products->getCollection()->mapWithKeys(function (Product $product): array {
            $gallery = $product->getMedia('gallery')
                ->map(fn ($media): array => [
                    'url' => $media->getUrl(),
                    'alt' => 'تصویر '.$product->title,
                ]);

            $variantPhotos = $product->variants
                ->map(fn ($variant) => $variant->getFirstMedia('variant-image'))
                ->filter()
                ->map(fn ($media): array => [
                    'url' => $media->getUrl(),
                    'alt' => 'تصویر '.$product->title,
                ]);

            return [(string) $product->id => [
                'title' => $product->title,
                'category' => $product->category->name,
                'summary' => $product->summary,
                'description' => $product->description,
                'images' => $gallery->concat($variantPhotos)->unique('url')->take(12)->values()->all(),
                'features' => $product->features
                    ->map(fn ($feature): array => ['name' => $feature->name, 'value' => $feature->value])
                    ->values()->all(),
                'variants' => $product->variants
                    ->map(fn ($variant): array => [
                        'title' => $variant->title,
                        'size' => rtrim(rtrim((string) $variant->sale_quantity, '0'), '.').' '.match ($variant->sale_unit) {
                            'g' => 'گرم',
                            'kg' => 'کیلوگرم',
                            'l' => 'لیتر',
                            'piece' => 'عدد',
                            'pack' => 'بسته',
                            default => $variant->sale_unit,
                        },
                        'price' => Money::formatToman((int) $variant->price_irr),
                        'image' => $variant->getFirstMedia('variant-image')?->getUrl(),
                    ])
                    ->values()->all(),
            ]];
        })->all();

        return view('landing.index', compact('categories', 'products', 'selectedCategory', 'telegramUrl', 'productPreviews'));
    }
}
