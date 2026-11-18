<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Image\Enums\Fit;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Product extends Model implements HasMedia
{
    use InteractsWithMedia, SoftDeletes;

    protected $fillable = ['category_id', 'title', 'slug', 'summary', 'description', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function features(): HasMany
    {
        return $this->hasMany(ProductFeature::class)->orderBy('sort_order');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(ProductVariant::class)->orderBy('sort_order');
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Keep the original untouched. Square, color-matched WebP derivatives
        // prevent letterboxing in the public gallery and avoid downloading
        // full resolution images for small thumbnails.
        $this->addMediaConversion('product-square')
            ->performOnCollections('gallery')
            ->fit(Fit::Crop, 960, 960)
            ->format('webp')
            ->quality(84)
            ->nonQueued();

        $this->addMediaConversion('product-thumb')
            ->performOnCollections('gallery')
            ->fit(Fit::Crop, 192, 192)
            ->format('webp')
            ->quality(80)
            ->nonQueued();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('gallery')
            ->useDisk('public')
            ->acceptsFile(fn (File $file) => str_starts_with($file->mimeType, 'image/'));
    }

    /**
     * Shared indexability and sellability contract for the homepage,
     * product permalinks and XML sitemap. Never index archived/unavailable items.
     */
    public function scopePubliclyOrderable(Builder $query): Builder
    {
        return $query->published()
            ->whereHas('variants', fn (Builder $variantQuery) => $variantQuery->availableForSale());
    }

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true));
    }
}
