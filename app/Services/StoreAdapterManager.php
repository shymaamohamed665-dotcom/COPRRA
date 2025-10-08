<?php

declare(strict_types=1);

namespace App\Services;

use App\Contracts\StoreAdapter;

/**
 * Manager for store adapters.
 */
final class StoreAdapterManager
{
    /**
     * @var array<string, StoreAdapter>
     */
    protected array $adapters = [];

    /**
     * @param  iterable<StoreAdapter>  $adapters
     */
    public function __construct(iterable $adapters)
    {
        foreach ($adapters as $adapter) {
            $this->register($adapter);
        }
    }

    /**
     * Register a store adapter.
     */
    public function register(StoreAdapter $adapter): void
    {
        $this->adapters[$adapter->getStoreIdentifier()] = $adapter;
    }

    /**
     * Get adapter by store identifier.
     */
    public function getAdapter(string $storeIdentifier): ?StoreAdapter
    {
        return $this->adapters[$storeIdentifier] ?? null;
    }

    /**
     * Get all available (configured) adapters.
     *
     * @return array<string, StoreAdapter>
     */
    public function getAvailableAdapters(): array
    {
        return array_filter(
            $this->adapters,
            fn (StoreAdapter $adapter) => $adapter->isAvailable()
        );
    }

    /**
     * Fetch product from specific store.
     *
     * @return array<string, string|int|float|null>|null
     */
    public function fetchProduct(string $storeIdentifier, string $productIdentifier): ?array
    {
        $adapter = $this->getAdapter($storeIdentifier);

        if (! $adapter) {
            return null;
        }

        if (! $adapter->isAvailable()) {
            return null;
        }

        return $adapter->fetchProduct($productIdentifier);
    }

    /**
     * Get list of available store identifiers.
     *
     * @return list<string>
     */
    public function getAvailableStores(): array
    {
        return array_keys($this->getAvailableAdapters());
    }
}
