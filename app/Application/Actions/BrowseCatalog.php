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
        $term = PersianSearch::normalize((string) $term);

        return Product::query()
            ->publiclyOrderable()
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->when($term !== '', function ($q) use ($term): void {
                $q->where(function ($inner) use ($term): void {
                    foreach (['title', 'summary', 'description'] as $column) {
                        $inner->orWhereRaw(PersianSearch::sql($column).' LIKE ?', ['%'.$term.'%']);
                    }
                });
            })
            ->with(['category', 'variants' => fn ($q) => $q->availableForSale()->orderBy('price_irr')])
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
