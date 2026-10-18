<?php

namespace App\Models;

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

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('variant-image')
            ->useDisk('public')
            ->singleFile()
            ->acceptsFile(fn (File $file) => str_starts_with($file->mimeType, 'image/'));
    }
}
