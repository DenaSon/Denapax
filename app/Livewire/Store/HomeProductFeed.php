<?php

namespace App\Livewire\Store;

use App\Models\Product;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Locked;
use Livewire\Component;

class HomeProductFeed extends Component
{
    private const PAGE_SIZE = 12;

    private const AUTO_BATCHES = 3;

    private const MAX_BATCHES = 30;

    #[Locked]
    public int $startPage = 1;

    #[Locked]
    public int $loadedBatches = 1;

    #[Locked]
    public int $anchorId = 0;

    public function mount(int $startPage = 1): void
    {
        $this->startPage = max(1, $startPage);
        // Newly published products must not shift existing offsets during scrolling.
        $this->anchorId = (int) Product::query()->publiclyOrderable()->max('products.id');
    }

    public function loadMore(): void
    {
        if ($this->loadedBatches < self::MAX_BATCHES) {
            $this->loadedBatches++;
        }
    }

    public function render(): View
    {
        $visibleLimit = $this->loadedBatches * self::PAGE_SIZE;

        $products = Product::query()
            ->publiclyOrderable()
            ->where('products.id', '<=', $this->anchorId)
            ->with([
                'category',
                'media',
                'variants' => fn ($query) => $query->availableForSale()->orderBy('sort_order'),
                'variants.media',
            ])
            ->orderByDesc('products.id')
            ->offset(($this->startPage - 1) * self::PAGE_SIZE)
            ->limit($visibleLimit + 1)
            ->get();

        $hasMore = $products->count() > $visibleLimit;

        return view('livewire.store.home-product-feed', [
            'products' => $products->take($visibleLimit),
            'hasMore' => $hasMore,
            'canLoadMore' => $hasMore && $this->loadedBatches < self::MAX_BATCHES,
            'autoload' => $hasMore && $this->loadedBatches < self::AUTO_BATCHES,
            'nextPageUrl' => route('home', ['page' => $this->startPage + $this->loadedBatches]).'#products',
        ]);
    }
}
