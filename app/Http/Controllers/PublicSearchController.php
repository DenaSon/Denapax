<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Support\PersianSearch;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class PublicSearchController extends Controller
{
    public function __invoke(Request $request): View
    {
        $requestedSearch = $request->query('q');
        $search = is_string($requestedSearch) && mb_strlen($requestedSearch) <= 80
            ? trim($requestedSearch)
            : '';
        $searchTerms = PersianSearch::terms($search);

        $products = Product::query()
            ->publiclyOrderable()
            ->when($searchTerms === [], fn (Builder $query) => $query->whereRaw('1 = 0'))
            ->when($searchTerms !== [], function (Builder $query) use ($searchTerms): void {
                foreach ($searchTerms as $term) {
                    $like = '%'.$term.'%';

                    $query->where(function (Builder $match) use ($like): void {
                        foreach (['title', 'summary', 'description', 'slug'] as $column) {
                            $match->orWhereRaw(PersianSearch::sql($column).' LIKE ?', [$like]);
                        }

                        $match->orWhereHas('category', fn (Builder $category) => $category
                            ->whereRaw(PersianSearch::sql('name').' LIKE ?', [$like]));
                    });
                }
            })
            ->with([
                'category',
                'media',
                'variants' => fn ($query) => $query->availableForSale()->orderBy('sort_order'),
                'variants.media',
            ])
            ->latest('id')
            ->paginate(12)
            ->withQueryString();

        return view('landing.search', compact('search', 'products'));
    }
}
