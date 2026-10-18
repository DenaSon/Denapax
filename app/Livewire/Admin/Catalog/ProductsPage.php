<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Livewire\WithPagination;

class ProductsPage extends Component
{
    use WithPagination;

    public string $search = '';

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggle(int $id): void
    {
        Gate::authorize('catalog.manage');
        $product = Product::findOrFail($id);
        $product->update(['is_active' => ! $product->is_active]);
        activity()->causedBy(auth()->user())->performedOn($product)->log('product.visibility_changed');
    }

    public function render()
    {
        Gate::authorize('catalog.manage');

        return view('livewire.admin.catalog.products-page', [
            'products' => Product::query()->with(['category', 'variants'])
                ->when($this->search !== '', function ($query): void {
                    $query->where('title', 'like', '%'.$this->search.'%');
                })
                ->latest()->paginate(12),
        ])->layout('layouts.admin');
    }
}
