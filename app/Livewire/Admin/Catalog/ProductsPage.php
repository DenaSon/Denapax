<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Product;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
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
        $product = Product::with('category')->findOrFail($id);
        if (! $product->is_active && (! $product->category->is_active || ! $product->variants()->exists())) {
            throw ValidationException::withMessages(['publish' => 'برای انتشار، دسته‌بندی فعال و دست‌کم یک تنوع لازم است.']);
        }

        $product->update(['is_active' => ! $product->is_active]);
        activity()->causedBy(auth()->user())->performedOn($product)->log('product.visibility_changed');
    }

    public function archive(int $id): void
    {
        Gate::authorize('catalog.manage');
        $product = Product::findOrFail($id);
        $product->update(['is_active' => false]);
        $product->delete();
        activity()->causedBy(auth()->user())->performedOn($product)->log('product.archived');
        session()->flash('status', 'محصول بایگانی شد.');
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
