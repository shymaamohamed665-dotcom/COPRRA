<?php

declare(strict_types=1);

namespace App\Contracts;

/**
 * Interface for store adapters.
 *
 * Each store adapter must implement this interface to provide
 * a unified way to fetch product data from different stores.
 */
interface StoreAdapter
{
    /**
     * Get the store name.
     */
    public function getStoreName(): string;

    /**
     * Get the store identifier.
     */
    public function getStoreIdentifier(): string;

    /**
     * Check if the adapter is available and configured.
     */
    public function isAvailable(): bool;

    /**
     * Fetch product data by product identifier.
     *
     * @param  string  $productIdentifier  The product ID/SKU/ASIN
     *
     * @return array<string, string|float|int|array<string, string>|null>|null
     */
    public function fetchProduct(string $productIdentifier): ?array;

    /**
     * Search for products by query.
     *
     * @param  string  $query  Search query
     * @param  array<string, string|int|float|bool|null>  $options  Search options (limit, page, filters, etc.)
     *
     * @return array<int, array<string, string|float|int|array<string, string>|null>>
     */
    public function searchProducts(string $query, array $options = []): array;

    /**
     * Validate product identifier format.
     */
    public function validateIdentifier(string $identifier): bool;

    /**
     * Get the product URL from identifier.
     */
    public function getProductUrl(string $identifier): string;

    /**
     * Get rate limit information.
     *
     * @return array{
     *     requests_per_minute: int,
     *     requests_per_hour: int,
     *     requests_per_day: int
     * }
     */
    public function getRateLimits(): array;
}
