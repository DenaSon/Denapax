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
        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $query) => $query->publiclyOrderable())
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
            ->publiclyOrderable()
            ->when($selectedCategory !== null, fn (Builder $query) => $query
                ->whereHas('category', fn (Builder $category) => $category->where('slug', $selectedCategory)))
            ->with([
                'category',
                'media',
                'variants' => fn ($query) => $query->availableForSale()->orderBy('sort_order'),
                'variants.media',
            ])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => 'DenaPax',
            'url' => route('home'),
            'inLanguage' => 'fa-IR',
        ];

        return view('landing.index', compact(
            'categories',
            'products',
            'selectedCategory',
            'websiteSchema',
        ));
    }
}
