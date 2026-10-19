<?php

namespace App\Livewire\Admin\Catalog;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Component;
use Livewire\WithFileUploads;

class ProductEditor extends Component
{
    use WithFileUploads;

    public ?int $productId = null;

    public int $categoryId = 0;

    public string $title = '';

    public string $slug = '';

    // Keep existing slugs and intentional manual overrides untouched.
    public bool $slugManuallyEdited = false;

    public string $summary = '';

    public string $description = '';

    public bool $isActive = false;

    public array $features = [];

    public array $variants = [];

    public array $galleryUploads = [];

    public array $variantUploads = [];

    public function mount(?Product $product = null): void
    {
        Gate::authorize('catalog.manage');

        if ($product?->exists) {
            $this->productId = $product->id;
            $this->categoryId = $product->category_id;
            $this->title = $product->title;
            $this->slug = $product->slug;
            $this->slugManuallyEdited = true;
            $this->summary = $product->summary;
            $this->description = $product->description ?? '';
            $this->isActive = $product->is_active;
            $this->features = $product->features->map(fn ($feature) => [
                'name' => $feature->name,
                'value' => $feature->value,
            ])->all();
            $this->variants = $product->variants->map(fn ($variant) => [
                'id' => $variant->id,
                'sku' => $variant->sku,
                'title' => $variant->title,
                'sale_quantity' => $variant->sale_quantity,
                'sale_unit' => $variant->sale_unit,
                'price_toman' => Money::tomanInput($variant->price_irr),
                'shipping_weight_grams' => $variant->shipping_weight_grams,
                'is_available' => $variant->is_available,
            ])->all();
        }
    }

    public function updatedTitle(): void
    {
        if (! $this->slugManuallyEdited && $this->productId === null) {
            $this->slug = Str::slug($this->title);
        }
    }

    public function updatedSlug(): void
    {
        $this->slugManuallyEdited = true;
    }

    public function addFeature(): void
    {
        Gate::authorize('catalog.manage');
        $this->features[] = ['name' => '', 'value' => ''];
    }

    public function removeFeature(int $index): void
    {
        Gate::authorize('catalog.manage');
        unset($this->features[$index]);
        $this->features = array_values($this->features);
    }

    public function addVariant(): void
    {
        Gate::authorize('catalog.manage');
        $this->variants[] = [
            'id' => null,
            'sku' => $this->generateSku(),
            'title' => '',
            'sale_quantity' => '1',
            'sale_unit' => 'piece',
            'price_toman' => '0',
            'shipping_weight_grams' => 1,
            'is_available' => true,
        ];
    }

    private function generateSku(): string
    {
        // The database unique index remains the final authority. Pre-check
        // existing rows and currently unsaved variants for a useful default.
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $candidate = 'DN-'.random_int(1000000, 9999999);
            $alreadyInForm = collect($this->variants)
                ->contains(fn (array $variant) => ($variant['sku'] ?? null) === $candidate);

            if (! $alreadyInForm && ! ProductVariant::query()->where('sku', $candidate)->exists()) {
                return $candidate;
            }
        }

        throw new \RuntimeException('Unable to generate unique SKU');
    }

    public function removeVariant(int $index): void
    {
        Gate::authorize('catalog.manage');
        if (! isset($this->variants[$index])) {
            return;
        }

        if ($this->variants[$index]['id']) {
            // Keep historical references stable: existing variants are archived by availability.
            $this->variants[$index]['is_available'] = false;

            return;
        }

        unset($this->variants[$index]);
        $this->variants = array_values($this->variants);
        $this->variantUploads = [];
    }

    public function removeGalleryImage(int $mediaId): void
    {
        Gate::authorize('catalog.manage');
        $product = Product::findOrFail($this->productId);
        $media = $product->getMedia('gallery')->firstWhere('id', $mediaId);
        abort_unless($media, 404);
        $media->delete();
        activity()->causedBy(auth()->user())->performedOn($product)->log('product.image_removed');
    }

    public function save()
    {
        Gate::authorize('catalog.manage');

        $this->features = array_values(array_filter($this->features, fn ($row) => trim((string) ($row['name'] ?? '')) !== '' || trim((string) ($row['value'] ?? '')) !== ''));
        foreach ($this->variants as &$variant) {
            $variant['price_toman'] = Money::normalizeTomanInput((string) ($variant['price_toman'] ?? ''));
        }
        unset($variant);

        $data = $this->validate([
            'categoryId' => 'required|integer|exists:categories,id',
            'title' => 'required|string|max:220',
            'slug' => ['required', 'string', 'max:191', 'regex:/^[a-z0-9-]+$/', Rule::unique('products', 'slug')->ignore($this->productId)],
            'summary' => 'required|string|max:600',
            'description' => 'string',
            'isActive' => 'boolean',
            'features' => 'array',
            'features.*.name' => 'required|string|max:100',
            'features.*.value' => 'required|string|max:500',
            'variants' => 'array',
            'variants.*.id' => 'nullable|integer',
            'variants.*.sku' => 'required|string|max:100|distinct',
            'variants.*.title' => 'required|string|max:180',
            'variants.*.sale_quantity' => 'required|numeric|decimal:0,3|gt:0',
            'variants.*.sale_unit' => ['required', Rule::in(['g', 'kg', 'l', 'piece'])],
            'variants.*.price_toman' => ['required', 'string', 'max:16', 'regex:/^(0|[1-9][0-9]*)(?:\.[0-9])?$/D'],
            'variants.*.shipping_weight_grams' => 'required|integer|min:1',
            'variants.*.is_available' => 'boolean',
            'galleryUploads' => 'array',
            'galleryUploads.*' => 'image|mimes:jpeg,png,webp|max:4096',
            'variantUploads' => 'array',
            'variantUploads.*' => 'image|mimes:jpeg,png,webp|max:4096',
        ]);

        if ($this->isActive && $this->variants === []) {
            $this->addError('variants', 'محصول فعال باید دست‌کم یک تنوع داشته باشد.');

            return;
        }

        if ($this->isActive && ! collect($data['variants'])->contains(fn ($row) => ! empty($row['is_available']))) {
            $this->addError('variants', 'برای انتشار محصول، حداقل یک تنوع باید قابل سفارش باشد.');

            return;
        }

        if ($this->isActive && collect($data['variants'])->contains(fn ($row) => ! empty($row['is_available']) && Money::tomanToIrr($row['price_toman']) === 0)) {
            $this->addError('variants', 'قیمت تنوع قابل سفارش برای محصول منتشرشده باید بیشتر از صفر باشد.');

            return;
        }

        foreach ($this->variants as $i => $row) {
            if (! empty($row['id']) && ! ProductVariant::where('product_id', $this->productId)->whereKey($row['id'])->exists()) {
                $this->addError("variants.$i.sku", 'تنوع متعلق به این محصول نیست.');

                return;
            }
            if (ProductVariant::where('sku', $row['sku'])->when(! empty($row['id']), fn ($q) => $q->whereKeyNot($row['id']))->exists()) {
                $this->addError("variants.$i.sku", 'کد SKU تکراری است.');

                return;
            }
        }

        $product = DB::transaction(function () use ($data): Product {
            $product = $this->productId ? Product::findOrFail($this->productId) : new Product;
            $product->fill([
                'category_id' => $data['categoryId'],
                'title' => $data['title'],
                'slug' => $data['slug'],
                'summary' => $data['summary'],
                'description' => $data['description'],
                'is_active' => $data['isActive'],
            ])->save();

            $product->features()->delete();
            foreach ($data['features'] as $i => $row) {
                $product->features()->create($row + ['sort_order' => $i]);
            }

            foreach ($data['variants'] as $i => $row) {
                $variant = ! empty($row['id'])
                    ? $product->variants()->findOrFail($row['id'])
                    : new ProductVariant;
                $variant->fill([
                    'product_id' => $product->id,
                    'sku' => $row['sku'],
                    'title' => $row['title'],
                    'sale_quantity' => $row['sale_quantity'],
                    'sale_unit' => $row['sale_unit'],
                    'price_irr' => Money::tomanToIrr($row['price_toman']),
                    'shipping_weight_grams' => $row['shipping_weight_grams'],
                    'is_available' => $row['is_available'],
                    'sort_order' => $i,
                ])->save();
            }

            activity()->causedBy(auth()->user())->performedOn($product)->log('product.saved');

            return $product;
        });

        foreach ($this->galleryUploads as $image) {
            $product->addMedia($image->getRealPath())
                ->usingFileName($image->getClientOriginalName())
                ->toMediaCollection('gallery', 'public');
        }
        foreach ($this->variantUploads as $i => $image) {
            $variant = $product->variants()->orderBy('sort_order')->get()->get($i);
            if ($variant) {
                $variant->addMedia($image->getRealPath())
                    ->usingFileName($image->getClientOriginalName())
                    ->toMediaCollection('variant-image', 'public');
            }
        }

        session()->flash('status', 'محصول ذخیره شد.');

        return redirect()->route('admin.products.edit', ['product' => $product->id]);
    }

    public function render()
    {
        Gate::authorize('catalog.manage');

        return view('livewire.admin.catalog.product-editor', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->get(),
            'product' => $this->productId ? Product::with('media', 'variants.media')->findOrFail($this->productId) : null,
        ])->layout('layouts.admin');
    }
}
