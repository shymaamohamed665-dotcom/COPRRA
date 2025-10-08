<?php

declare(strict_types=1);

namespace App\Services\Contracts;

/**
 * Interface for store adapters.
 */
interface StoreAdapterContract
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
     * Check if the adapter is available.
     */
    public function isAvailable(): bool;

    /**
     * Fetch product data by identifier.
     *
     * @return array<string, string|int|float|bool|null>|null
     */
    public function fetchProduct(string $productIdentifier): ?array;

    /**
     * Search for products.
     *
     * @param  array<string, string|int|float|bool|null>  $options
     * @return array<int, array<string, string|int|float|bool|null>>
     */
    public function searchProducts(string $query, array $options = []): array;

    /**
     * Validate product identifier.
     */
    public function validateIdentifier(string $identifier): bool;

    /**
     * Get product URL.
     */
    public function getProductUrl(string $identifier): string;

    /**
     * Get rate limits.
     *
     * @return array<string, int>
     */
    public function getRateLimits(): array;
}
