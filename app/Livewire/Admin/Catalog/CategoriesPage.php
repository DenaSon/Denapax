<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Category;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

class CategoriesPage extends Component
{
    public ?int $editing = null;

    public string $name = '';

    public string $slug = '';

    public bool $isActive = true;

    public int $sortOrder = 0;

    public function edit(int $id): void
    {
        Gate::authorize('catalog.manage');
        $category = Category::findOrFail($id);
        $this->editing = $category->id;
        $this->name = $category->name;
        $this->slug = $category->slug;
        $this->isActive = $category->is_active;
        $this->sortOrder = $category->sort_order;
    }

    public function remove(int $id): void
    {
        Gate::authorize('catalog.manage');
        $category = Category::findOrFail($id);
        if ($category->products()->withTrashed()->exists()) {
            throw ValidationException::withMessages(['category' => 'ابتدا محصولات این دسته را به دسته دیگری منتقل کنید.']);
        }

        activity()->causedBy(auth()->user())->performedOn($category)->log('category.removed');
        $category->delete();
    }

    public function resetForm(): void
    {
        $this->reset('editing', 'name', 'slug', 'sortOrder');
        $this->isActive = true;
    }

    public function save(): void
    {
        Gate::authorize('catalog.manage');
        $data = $this->validate([
            'name' => 'required|string|max:160',
            'slug' => ['required', 'string', 'max:180', 'regex:/^[a-z0-9-]+$/', Rule::unique('categories', 'slug')->ignore($this->editing)],
            'isActive' => 'boolean',
            'sortOrder' => 'integer|min:0',
        ]);
        $category = $this->editing ? Category::findOrFail($this->editing) : new Category;
        $category->fill([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'is_active' => $data['isActive'],
            'sort_order' => $data['sortOrder'],
        ])->save();
        activity()->causedBy(auth()->user())->performedOn($category)->log('category.saved');
        $this->resetForm();
        session()->flash('status', 'دسته‌بندی ذخیره شد.');
    }

    public function render()
    {
        Gate::authorize('catalog.manage');

        return view('livewire.admin.catalog.categories-page', [
            'categories' => Category::orderBy('sort_order')->orderBy('name')->get(),
        ])->layout('layouts.admin');
    }
}
