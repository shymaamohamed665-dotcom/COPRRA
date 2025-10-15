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
     * @return array<string, null|scalar|array>|null
     *
     * @psalm-return array{
     *   name: array|scalar,
     *   price: float,
     *   currency: array|scalar,
     *   url: array|scalar,
     *   image_url: array|null|scalar,
     *   availability: array|scalar,
     *   rating: float|null,
     *   reviews_count: int|null,
     *   description: array|null|scalar,
     *   brand: array|null|scalar,
     *   category: array|null|scalar,
     *   metadata: array|scalar
     * }|null
     */
    public function fetchProduct(string $productIdentifier): ?array;

    /**
     * Search for products.
     *
     * @param  array<string, string|int|float|bool|null>  $options
     * @return array<int, array<string, null|scalar|array>>
     *
     * @psalm-return list<non-empty-array<string, null|scalar|array>>
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
