<?php

namespace App\Application\Actions;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class BrowseCatalog
{
    public function categories()
    {
        return Category::query()->where('is_active', true)->orderBy('sort_order')->orderBy('name')->get();
    }

    public function products(?int $categoryId = null, ?string $term = null, int $page = 1): LengthAwarePaginator
    {
        $term = trim((string) $term);

        return Product::query()
            ->published()
            ->whereHas('variants', fn ($q) => $q->where('is_available', true))
            ->when($categoryId !== null, fn ($q) => $q->where('category_id', $categoryId))
            ->when($term !== '', function ($q) use ($term): void {
                $q->where(function ($inner) use ($term): void {
                    $inner->where('title', 'like', '%'.$term.'%')
                        ->orWhere('description', 'like', '%'.$term.'%');
                });
            })
            ->with(['category', 'variants' => fn ($q) => $q->where('is_available', true)->orderBy('price_irr')])
            ->orderBy('id')
            ->paginate(perPage: 5, page: max(1, $page));
    }

    public function product(int $id): ?Product
    {
        return Product::query()->published()
            ->whereHas('variants', fn ($q) => $q->where('is_available', true))
            ->with([
                'features',
                'media',
                'variants' => fn ($q) => $q->where('is_available', true)->orderBy('sort_order'),
                'variants.media',
            ])
            ->find($id);
    }
}
