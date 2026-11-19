<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\StoreSeo;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PublicLandingController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $categories = Category::query()
            ->where('is_active', true)
            ->whereHas('products', fn (Builder $query) => $query->publiclyOrderable())
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $requestedCategory = $request->query('category');
        // Preserve old category-filter links, but consolidate SEO on one
        // public, indexable URL per category instead of query-string duplicates.
        if ($request->has('category')) {
            if (is_string($requestedCategory) && $categories->contains('slug', $requestedCategory)) {
                $parameters = ['slug' => $requestedCategory];
                $page = $request->query('page');
                if (is_string($page) && ctype_digit($page) && (int) $page > 1) {
                    $parameters['page'] = (int) $page;
                }

                return redirect()->route('categories.show', $parameters, 301);
            }

            return redirect()->route('home', [], 301);
        }

        $selectedCategory = null;
        // Lightweight page validation/metadata; the Livewire feed loads product media.
        $products = Product::query()
            ->publiclyOrderable()
            ->select('products.id')
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        if ($products->currentPage() > $products->lastPage()) {
            abort(404);
        }

        $canonicalUrl = $products->currentPage() > 1
            ? route('home', ['page' => $products->currentPage()])
            : route('home');

        $websiteSchema = [
            '@context' => 'https://schema.org',
            '@graph' => [StoreSeo::website(), StoreSeo::organization()],
        ];

        return view('landing.index', compact(
            'categories',
            'products',
            'selectedCategory',
            'canonicalUrl',
            'websiteSchema',
        ));
    }
}
