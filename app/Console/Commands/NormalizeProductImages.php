<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\ProductImageNormalizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class NormalizeProductImages extends Command
{
    protected $signature = 'denapax:products:normalize-images
        {--dry-run : Inspect only; do not write files or media attributes}
        {--id= : Process one media ID}
        {--limit=0 : Maximum number of media items (0 = all)}';

    protected $description = 'Backfill square product image conversions without changing originals or private receipts';

    public function handle(ProductImageNormalizer $normalizer): int
    {
        $query = Media::query()
            ->where(function ($query) {
                $query->where(function ($query) {
                    $query->where('model_type', Product::class)->where('collection_name', 'gallery');
                })->orWhere(function ($query) {
                    $query->where('model_type', ProductVariant::class)->where('collection_name', 'variant-image');
                });
            })
            ->orderBy('id');

        $id = $this->option('id');
        if ($id !== null) {
            if (! ctype_digit((string) $id) || (int) $id < 1) {
                $this->error('--id must be a positive media ID.');

                return self::FAILURE;
            }
            $query->whereKey((int) $id);
        }

        $limit = $this->option('limit');
        if (! ctype_digit((string) $limit)) {
            $this->error('--limit must be a non-negative integer.');

            return self::FAILURE;
        }
        if ((int) $limit > 0) {
            $query->limit((int) $limit);
        }

        $dryRun = (bool) $this->option('dry-run');
        $processed = 0;
        $failed = 0;

        foreach ($query->cursor() as $media) {
            try {
                $originalPath = $media->getPath();
                if (! is_file($originalPath)) {
                    throw new \RuntimeException('Original file is not accessible on the configured media disk.');
                }

                $generated = $normalizer->manipulationsFor($originalPath);
                $manipulations = $media->manipulations ?? [];

                foreach (['product-square', 'product-thumb'] as $conversion) {
                    if (isset($generated[$conversion]['manualCrop'])) {
                        $manipulations[$conversion]['manualCrop'] = $generated[$conversion]['manualCrop'];
                    } else {
                        unset($manipulations[$conversion]['manualCrop']);
                        if (($manipulations[$conversion] ?? []) === []) {
                            unset($manipulations[$conversion]);
                        }
                    }
                }

                if (! $dryRun) {
                    if ($media->manipulations !== $manipulations) {
                        // Regenerate explicitly below; do not process the image
                        // twice through Spatie's manipulation-change observer.
                        $media->manipulations = $manipulations;
                        $media->saveQuietly();
                    }

                    Artisan::call('media-library:regenerate', [
                        '--ids' => [(string) $media->id],
                        '--only' => ['product-square', 'product-thumb'],
                    ]);
                }

                $processed++;
                $this->line(sprintf(
                    '%s media #%d (%s%s)',
                    $dryRun ? '[dry-run]' : '[done]',
                    $media->id,
                    $media->collection_name,
                    $generated === [] ? ', square fit' : ', white border trimmed'
                ));
            } catch (Throwable $exception) {
                $failed++;
                $this->error("Media #{$media->id}: {$exception->getMessage()}");
            }
        }

        $this->info("Product images: {$processed} processed, {$failed} failed.".($dryRun ? ' (dry run)' : ''));

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
