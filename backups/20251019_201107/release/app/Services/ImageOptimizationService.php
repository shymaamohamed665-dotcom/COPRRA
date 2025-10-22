<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;

final class ImageOptimizationService
{
    /**
     * @param  array<string, array{0: int, 1: int}>  $sizes
     * @return array<string, array{path: string, url: string, width: int, height: int, size: int}>
     */
    public function optimizeImage(string $path, array $sizes = []): array
    {
        $defaultSizes = [
            'thumbnail' => [150, 150],
            'small' => [300, 300],
            'medium' => [600, 600],
            'large' => [1200, 1200],
        ];

        $sizes = array_merge($defaultSizes, $sizes);
        $optimizedImages = [];

        try {
            $originalPath = Storage::path($path);
            $image = Image::make($originalPath);

            foreach ($sizes as $sizeName => $dimensions) {
                if (! is_array($dimensions) || count($dimensions) < 2) {
                    continue;
                }

                $width = $dimensions[0];
                $height = $dimensions[1];

                $sizeNameStr = (string) $sizeName;
                $optimizedPath = $this->generateOptimizedPath($path, $sizeNameStr);

                $resizedImage = $image->resize($width, $height, static function (object $constraint): void {
                    if (method_exists($constraint, 'aspectRatio')) {
                        $constraint->aspectRatio();
                    }
                    if (method_exists($constraint, 'upsize')) {
                        $constraint->upsize();
                    }
                });

                // Convert to WebP for better compression
                $webpPath = str_replace(['.jpg', '.jpeg', '.png'], '.webp', $optimizedPath);
                $resizedImage->encode('webp', 85)->save(Storage::path($webpPath));

                $fileSize = filesize(Storage::path($webpPath));

                $optimizedImages[$sizeNameStr] = [
                    'path' => $webpPath,
                    'url' => Storage::url($webpPath),
                    'width' => $resizedImage->width(),
                    'height' => $resizedImage->height(),
                    'size' => $fileSize !== false ? $fileSize : 0,
                ];
            }

            return $optimizedImages;
        } catch (\Exception $e) {
            Log::error('Image optimization failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return [];
        }
    }

    public function generateResponsiveImages(string $originalPath): string
    {
        $optimizedImages = $this->optimizeImage($originalPath);

        return collect($optimizedImages)
            ->map(static function (array $image): string {
                return $image['url'].' '.$image['width'].'w';
            })
            ->implode(', ');
    }

    /**
     * @param  array<string, string|int|float|bool>  $attributes
     */
    public function lazyLoadImage(string $originalPath, string $alt = '', array $attributes = []): string
    {
        $optimizedImages = $this->optimizeImage($originalPath);
        $placeholder = $this->generatePlaceholder($originalPath);

        if (isset($optimizedImages['medium']) && is_array($optimizedImages['medium'])) {
            $mediumUrl = $optimizedImages['medium']['url'] ?? Storage::url($originalPath);
        } else {
            $mediumUrl = Storage::url($originalPath);
        }

        $attributes = array_merge([
            'class' => 'lazy-load',
            'data-src' => $mediumUrl,
            'data-srcset' => $this->generateResponsiveImages($originalPath),
            'alt' => $alt,
        ], $attributes);

        $attributesString = collect($attributes)
            ->map(static function (string|int|float|bool $value, string $key): string {
                if (! is_string($value) && ! is_numeric($value)) {
                    return '';
                }

                return $key.'="'.htmlspecialchars((string) $value).'"';
            })
            ->implode(' ');

        return '<img '.$attributesString.' src="'.$placeholder.'">';
    }

    public function compressImage(string $path, int $quality = 85): bool
    {
        try {
            $image = Image::make(Storage::path($path));
            $image->encode(null, $quality)->save();

            return true;
        } catch (\Exception $e) {
            Log::error('Image compression failed', [
                'path' => $path,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function generateOptimizedPath(string $originalPath, string $size): string
    {
        $pathInfo = pathinfo($originalPath);

        $dirname = $pathInfo['dirname'] ?? '';
        $filename = $pathInfo['filename'] ?? '';
        $extension = $pathInfo['extension'] ?? 'jpg';

        return $dirname.'/'.$filename.'_'.$size.'.'.$extension;
    }

    private function generatePlaceholder(string $originalPath): string
    {
        try {
            // Generate a low-quality placeholder
            $image = Image::make(Storage::path($originalPath))->resize(20, 20)->blur(10);
            $encoded = $image->encode('data-url');

            return is_string($encoded) ? $encoded : '';
        } catch (\Exception $e) {
            Log::error('Failed to generate placeholder', [
                'path' => $originalPath,
                'error' => $e->getMessage(),
            ]);

            return '';
        }
    }
}
