<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Contracts\StoreAdapterContract;
use App\Services\StoreAdapters\AmazonAdapter;
use App\Services\StoreAdapters\EbayAdapter;
use App\Services\StoreAdapters\NoonAdapter;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Manager for store adapters.
 */
final class StoreAdapterManager
{
    /**
     * @var array<string, StoreAdapterContract>
     */
    protected array $adapters = [];

    /**
     * @param  iterable<StoreAdapterContract>  $adapters
     */
    public function __construct(iterable $adapters)
    {
        // If no adapters provided, register default adapters
        $adaptersArray = is_array($adapters) ? $adapters : iterator_to_array($adapters);

        if (empty($adaptersArray)) {
            $this->registerDefaultAdapters();
        } else {
            foreach ($adapters as $adapter) {
                $this->register($adapter);
            }
        }
    }

    /**
     * Register default store adapters.
     */
    private function registerDefaultAdapters(): void
    {
        $http = app(HttpFactory::class);
        $cache = app(CacheRepository::class);
        $logger = app(LoggerInterface::class);

        $this->register(new AmazonAdapter($http, $cache, $logger));
        $this->register(new EbayAdapter($http, $cache, $logger));
        $this->register(new NoonAdapter($http, $cache, $logger));
    }

    /**
     * Register a store adapter.
     */
    public function register(StoreAdapterContract $adapter): void
    {
        $this->adapters[$adapter->getStoreIdentifier()] = $adapter;
    }

    /**
     * Get adapter by store identifier.
     */
    public function getAdapter(string $storeIdentifier): ?StoreAdapterContract
    {
        return $this->adapters[$storeIdentifier] ?? null;
    }

    /**
     * Get all registered adapters.
     *
     * @return array<string, StoreAdapterContract>
     */
    public function getAllAdapters(): array
    {
        return $this->adapters;
    }

    /**
     * Get all available (configured) adapters.
     *
     * @return array<string, StoreAdapterContract>
     */
    public function getAvailableAdapters(): array
    {
        return array_filter(
            $this->adapters,
            fn (StoreAdapterContract $adapter) => $adapter->isAvailable()
        );
    }

    /**
     * Check if a store is supported.
     */
    public function isStoreSupported(string $storeIdentifier): bool
    {
        return isset($this->adapters[$storeIdentifier]);
    }

    /**
     * Get list of supported store identifiers.
     *
     * @return list<string>
     */
    public function getSupportedStores(): array
    {
        return array_keys($this->adapters);
    }

    /**
     * Validate product identifier for a store.
     */
    public function validateIdentifier(string $storeIdentifier, string $identifier): bool
    {
        $adapter = $this->getAdapter($storeIdentifier);

        if (! $adapter) {
            return false;
        }

        return $adapter->validateIdentifier($identifier);
    }

    /**
     * Get product URL for a store.
     */
    public function getProductUrl(string $storeIdentifier, string $identifier): ?string
    {
        $adapter = $this->getAdapter($storeIdentifier);

        if (! $adapter) {
            return null;
        }

        return $adapter->getProductUrl($identifier);
    }

    /**
     * Get statistics about registered adapters.
     *
     * @return ((bool|string)[][]|int)[]
     *
     * @psalm-return array{total_adapters: int<0, max>, available_adapters: int<0, max>, adapters: array<string, array{name: string, identifier: string, available: bool}>}
     */
    public function getStatistics(): array
    {
        $adaptersInfo = [];
        foreach ($this->adapters as $identifier => $adapter) {
            $adaptersInfo[$identifier] = [
                'name' => $adapter->getStoreName(),
                'identifier' => $adapter->getStoreIdentifier(),
                'available' => $adapter->isAvailable(),
            ];
        }

        return [
            'total_adapters' => count($this->adapters),
            'available_adapters' => count($this->getAvailableAdapters()),
            'adapters' => $adaptersInfo,
        ];
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
