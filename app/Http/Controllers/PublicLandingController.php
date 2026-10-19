<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
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
                'media',
                'variants' => fn (Builder $query) => $orderable($query)->orderBy('sort_order'),
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

        return view('landing.index', compact('categories', 'products', 'selectedCategory', 'telegramUrl'));
    }
}
