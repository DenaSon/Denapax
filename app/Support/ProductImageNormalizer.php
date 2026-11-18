<?php

namespace App\Support;

use GdImage;

/**
 * Detect only obvious white/transparent outer margins of product photographs.
 *
 * The original file is never modified. Spatie applies the resulting crop to
 * presentation-only media conversions before fitting them onto a square canvas.
 * Natural or ambiguous backgrounds are deliberately left uncropped.
 */
class ProductImageNormalizer
{
    private const CONVERSIONS = ['product-square', 'product-thumb'];

    /**
     * @return array<string, array{manualCrop: array{int, int, int, int}}>
     */
    public function manipulationsFor(string $path): array
    {
        $crop = $this->detectCrop($path);

        if ($crop === null) {
            return [];
        }

        return array_fill_keys(self::CONVERSIONS, ['manualCrop' => $crop]);
    }

    /**
     * @return array{int, int, int, int}|null [width, height, x, y]
     */
    public function detectCrop(string $path): ?array
    {
        if (! extension_loaded('gd') || ! is_file($path)) {
            return null;
        }

        $size = @getimagesize($path);
        if ($size === false) {
            return null;
        }

        [$width, $height] = $size;
        $type = $size[2] ?? null;

        // Do not decode enormous sources just to inspect their margins.
        if ($width < 32 || $height < 32 || $width * $height > 24_000_000) {
            return null;
        }

        // GD does not rotate JPEG pixels based on EXIF. Avoid cropping in
        // unrotated coordinates if another image driver honors orientation.
        if ($type === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $exif = @exif_read_data($path);
            if (isset($exif['Orientation']) && (int) $exif['Orientation'] !== 1) {
                return null;
            }
        }

        $original = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($path),
            IMAGETYPE_PNG => @imagecreatefrompng($path),
            IMAGETYPE_WEBP => @imagecreatefromwebp($path),
            default => false,
        };

        if (! $original instanceof GdImage) {
            return null;
        }

        try {
            $scale = min(1.0, 400 / max($width, $height));
            $sampleWidth = max(1, (int) round($width * $scale));
            $sampleHeight = max(1, (int) round($height * $scale));
            $sample = imagecreatetruecolor($sampleWidth, $sampleHeight);

            if (! $sample instanceof GdImage) {
                return null;
            }

            try {
                imagealphablending($sample, false);
                imagesavealpha($sample, true);
                $transparent = imagecolorallocatealpha($sample, 255, 255, 255, 127);
                imagefill($sample, 0, 0, $transparent);
                imagecopyresampled($sample, $original, 0, 0, 0, 0, $sampleWidth, $sampleHeight, $width, $height);

                $corners = [
                    [0, 0],
                    [$sampleWidth - 1, 0],
                    [0, $sampleHeight - 1],
                    [$sampleWidth - 1, $sampleHeight - 1],
                ];

                // If any corner isn't clearly background, this may be a
                // lifestyle photo or a product touching the edge. Leave it.
                foreach ($corners as [$x, $y]) {
                    if (! $this->isBackground($sample, $x, $y)) {
                        return null;
                    }
                }

                $left = $sampleWidth;
                $top = $sampleHeight;
                $right = -1;
                $bottom = -1;

                for ($y = 0; $y < $sampleHeight; $y++) {
                    for ($x = 0; $x < $sampleWidth; $x++) {
                        if ($this->isBackground($sample, $x, $y)) {
                            continue;
                        }

                        $left = min($left, $x);
                        $top = min($top, $y);
                        $right = max($right, $x);
                        $bottom = max($bottom, $y);
                    }
                }

                if ($right < $left || $bottom < $top) {
                    return null;
                }

                // Leave breathing room so kernels, fruit, packaging edges and
                // their shadows do not touch or get clipped by the frame.
                $margin = max(2, (int) ceil(max($right - $left + 1, $bottom - $top + 1) * .055));
                $x = max(0, (int) floor(($left - $margin) * $width / $sampleWidth));
                $y = max(0, (int) floor(($top - $margin) * $height / $sampleHeight));
                $endX = min($width, (int) ceil(($right + 1 + $margin) * $width / $sampleWidth));
                $endY = min($height, (int) ceil(($bottom + 1 + $margin) * $height / $sampleHeight));

                $cropWidth = $endX - $x;
                $cropHeight = $endY - $y;

                // Tiny margin changes are not worth manipulating the source.
                if ($cropWidth < 1 || $cropHeight < 1 ||
                    ($cropWidth >= $width * .94 && $cropHeight >= $height * .94)) {
                    return null;
                }

                return [$cropWidth, $cropHeight, $x, $y];
            } finally {
                imagedestroy($sample);
            }
        } finally {
            imagedestroy($original);
        }
    }

    private function isBackground(GdImage $image, int $x, int $y): bool
    {
        $pixel = imagecolorsforindex($image, imagecolorat($image, $x, $y));

        return $pixel['alpha'] >= 110 ||
            ($pixel['red'] >= 239 && $pixel['green'] >= 239 && $pixel['blue'] >= 239);
    }
}
