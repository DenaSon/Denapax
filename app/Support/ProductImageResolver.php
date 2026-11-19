<?php

namespace App\Support;

use App\Models\Product;
use App\Models\ProductVariant;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

final class ProductImageResolver
{
    /**
     * A specific package image wins, then the product gallery, then a sibling
     * package image. Media stays owned by its original product/variant.
     */
    public function forVariant(ProductVariant $variant, ?Product $product = null): ?Media
    {
        $dedicated = $variant->getFirstMedia('variant-image');

        if ($dedicated !== null) {
            return $dedicated;
        }

        $product ??= $variant->product;

        return $product !== null && (int) $product->id === (int) $variant->product_id
            ? $this->forProduct($product)
            : null;
    }

    /**
     * Product-level cover selection. Order sibling images deterministically
     * so their display cannot fluctuate between requests.
     */
    public function forProduct(Product $product): ?Media
    {
        $gallery = $product->getFirstMedia('gallery');

        if ($gallery !== null) {
            return $gallery;
        }

        foreach ($product->variants->sortBy([['sort_order', 'asc'], ['id', 'asc']]) as $variant) {
            $media = $variant->getFirstMedia('variant-image');

            if ($media !== null) {
                return $media;
            }
        }

        return null;
    }
}
