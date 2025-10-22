<?php

declare(strict_types=1);

namespace App\Services\Contracts;

/**
 * Contract for cache service operations used across the application.
 */
interface CacheServiceContract
{
    /**
     * Remember a value in cache or compute via callback on miss.
     *
     * @param  callable(): mixed  $callback
     * @param  array<int, string>  $tags
     */
    public function remember(string $key, int $ttl, callable $callback, array $tags = []): mixed;

    /**
     * Get a value from cache.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Forget a value by key.
     */
    public function forget(string $key): bool;

    /**
     * Forget caches by tags when supported.
     *
     * @param  array<int, string>  $tags
     */
    public function forgetByTags(array $tags): bool;

    /**
     * Flush entire cache store when appropriate.
     */
    public function clearAll(): bool;
}
