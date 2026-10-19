<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\File;

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
    public function scopePubliclyOrderable($query)
    {
        return $query->published()->whereHas('variants', fn ($variantQuery) => $variantQuery
            ->where('is_available', true)
            ->where('price_irr', '>', 0));
    }

    public function scopePublished($query)
    {
        return $query->where('is_active', true)
            ->whereHas('category', fn ($q) => $q->where('is_active', true));
    }
}
