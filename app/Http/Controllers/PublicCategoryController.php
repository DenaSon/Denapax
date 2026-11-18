<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Support\StoreSeo;
use Illuminate\Contracts\View\View;

class PublicCategoryController extends Controller
{
    public function __invoke(string $slug): View
    {
        $category = Category::query()
            ->where('slug', $slug)
            ->where('is_active', true)
            ->whereHas('products', fn ($query) => $query->publiclyOrderable())
            ->firstOrFail();

        $products = Product::query()
            ->publiclyOrderable()
            ->where('category_id', $category->id)
            ->with([
                'category',
                'media',
                'variants' => fn ($query) => $query->availableForSale()->orderBy('sort_order'),
                'variants.media',
            ])
            ->latest('id')
            ->paginate(12);

        // An empty page beyond the last pagination page is not an indexable page.
        if ($products->currentPage() > $products->lastPage()) {
            abort(404);
        }

        $canonicalUrl = $products->currentPage() > 1
            ? route('categories.show', ['slug' => $category->slug, 'page' => $products->currentPage()])
            : route('categories.show', ['slug' => $category->slug]);

        $seoTitle = 'خرید '.$category->name.' | محصولات دناپکس';
        $metaDescription = mb_substr(
            'محصولات دسته '.$category->name.' را با تصاویر، مشخصات بسته‌ها و قیمت به تومان در فروشگاه دناپکس بررسی کنید.',
            0,
            155,
        );

        $schema = [
            '@context' => 'https://schema.org',
            '@graph' => [
                [
                    '@type' => 'CollectionPage',
                    '@id' => $canonicalUrl.'#collection',
                    'name' => $seoTitle,
                    'description' => $metaDescription,
                    'url' => $canonicalUrl,
                    'inLanguage' => 'fa-IR',
                    'isPartOf' => ['@id' => route('home').'#website'],
                    'mainEntity' => [
                        '@type' => 'ItemList',
                        'itemListElement' => $products->getCollection()
                            ->values()
                            ->map(fn ($product, $index): array => [
                                '@type' => 'ListItem',
                                'position' => $products->firstItem() + $index,
                                'name' => $product->title,
                                'url' => route('products.show', ['slug' => $product->slug]),
                            ])
                            ->all(),
                    ],
                ],
                [
                    '@type' => 'BreadcrumbList',
                    'itemListElement' => [
                        [
                            '@type' => 'ListItem',
                            'position' => 1,
                            'name' => 'خانه',
                            'item' => route('home'),
                        ],
                        [
                            '@type' => 'ListItem',
                            'position' => 2,
                            'name' => $category->name,
                            'item' => route('categories.show', ['slug' => $category->slug]),
                        ],
                    ],
                ],
            ],
        ];

        return view('landing.category', compact(
            'category',
            'products',
            'canonicalUrl',
            'seoTitle',
            'metaDescription',
            'schema',
        ));
    }
}
