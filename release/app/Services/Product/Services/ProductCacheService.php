<?php

declare(strict_types=1);

namespace App\Services\Product\Services;

use Illuminate\Support\Facades\Cache;

/**
 * Service for handling product caching operations
 */
final class ProductCacheService
{
    /**
     * Cache search results
     *
     * @template T
     *
     * @param  array<string, mixed>  $filters
     * @param  callable(): T  $callback
     *
     * @return T
     */
    public function rememberSearch(string $query, array $filters, int $perPage, int $page, callable $callback)
    {
        $cacheKey = $this->generateSearchCacheKey($query, $filters, $perPage, $page);

        return Cache::remember($cacheKey, now()->addMinutes(15), $callback);
    }

    /**
     * Cache product by slug
     *
     * @template T
     *
     * @param  callable(): T  $callback
     *
     * @return T
     */
    public function rememberProductBySlug(string $slug, callable $callback)
    {
        $cacheKey = "product:slug:{$slug}:v1";

        return Cache::remember($cacheKey, now()->addHours(1), $callback);
    }

    /**
     * Cache related products
     *
     * @template T
     *
     * @param  callable(): T  $callback
     *
     * @return T
     */
    public function rememberRelatedProducts(int $productId, int $limit, callable $callback)
    {
        $cacheKey = "product:{$productId}:related:limit:{$limit}:v1";

        return Cache::remember($cacheKey, now()->addHours(1), $callback);
    }

    /**
     * Cache active products
     *
     * @template T
     *
     * @param  callable(): T  $callback
     *
     * @return T
     */
    public function rememberActiveProducts(int $perPage, callable $callback)
    {
        $cacheKey = "products:active:per_page:{$perPage}:page:".request()->get('page', 1);

        return Cache::remember($cacheKey, now()->addMinutes(15), $callback);
    }

    /**
     * Invalidate product caches
     *
     * @param  array<string>  $cacheKeys
     */
    public function invalidateCaches(array $cacheKeys): void
    {
        foreach ($cacheKeys as $key) {
            Cache::forget($key);
        }
    }

    /**
     * Generate search cache key
     *
     * @param  array<string, mixed>  $filters
     */
    private function generateSearchCacheKey(string $query, array $filters, int $perPage, int $page): string
    {
        $queryHash = hash('sha256', $query);
        $filtersJson = json_encode($filters);
        $filtersHash = hash('sha256', is_string($filtersJson) ? $filtersJson : '');

        return sprintf(
            'products:search:%s:%s:%d:%d',
            $queryHash,
            $filtersHash,
            $perPage,
            $page
        );
    }
}
