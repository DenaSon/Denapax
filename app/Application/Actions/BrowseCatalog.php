<?php

namespace App\Application\Actions;

use App\Models\Category;
use App\Models\Product;
use App\Support\PersianSearch;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrowseCatalog
{
    public function categories()
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function products(?int $categoryId = null, ?string $term = null, int $page = 1): LengthAwarePaginator
    {
        $terms = PersianSearch::terms((string) $term);

        return Product::query()
            ->publiclyOrderable()
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->when($terms !== [], function ($q) use ($terms): void {
                foreach ($terms as $term) {
                    $like = '%'.$term.'%';

                    $q->where(function ($inner) use ($like): void {
                        foreach (['title', 'summary', 'description', 'slug'] as $column) {
                            $inner->orWhereRaw(PersianSearch::sql($column).' LIKE ?', [$like]);
                        }

                        $inner->orWhereHas('category', fn ($category) => $category
                            ->whereRaw(PersianSearch::sql('name').' LIKE ?', [$like]));
                    });
                }
            })
            ->with([
                'category',
                'media',
                'variants' => fn ($q) => $q->availableForSale()->orderBy('price_irr'),
                'variants.media',
            ])
            ->orderBy('id')
            ->paginate(perPage: 10, page: max(1, $page));
    }

    public function product(int $id): ?Product
    {
        return Product::query()->publiclyOrderable()
            ->with([
                'features',
                'media',
                'variants' => fn ($q) => $q->availableForSale()->orderBy('sort_order'),
                'variants.media',
            ])
            ->find($id);
    }
}
