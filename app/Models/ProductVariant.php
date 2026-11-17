<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;

class ProductVariant extends Model implements HasMedia
{
    use InteractsWithMedia;

    protected $fillable = ['product_id', 'sku', 'title', 'sale_quantity', 'sale_unit', 'price_irr', 'shipping_weight_grams', 'is_available', 'sort_order'];

    protected function casts(): array
    {
        return [
            'sale_quantity' => 'decimal:3',
            'price_irr' => 'integer',
            'shipping_weight_grams' => 'integer',
            'is_available' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Variant-level requirements shared by public catalog and cart operations.
     */
    public function scopeAvailableForSale(Builder $query): Builder
    {
        return $query->where('is_available', true)
            ->where('price_irr', '>', 0)
            ->where('shipping_weight_grams', '>', 0);
    }

    /**
     * A sellable variant must also belong to a publicly published product.
     */
    public function scopeSellable(Builder $query): Builder
    {
        return $query->availableForSale()
            ->whereHas('product', fn (Builder $productQuery) => $productQuery->published());
    }

    public function isAvailableForSale(): bool
    {
        return $this->is_available
            && $this->price_irr > 0
            && $this->shipping_weight_grams > 0;
    }

    public function isSellable(): bool
    {
        $product = $this->product;

        return $this->isAvailableForSale()
            && $product?->is_active === true
            && $product->category?->is_active === true;
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant-image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file) => str_starts_with($file->mimeType, 'image/'));
    }
}
