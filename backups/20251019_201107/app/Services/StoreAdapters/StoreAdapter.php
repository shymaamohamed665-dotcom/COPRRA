<?php

declare(strict_types=1);

namespace App\Services\StoreAdapters;

use App\Services\Contracts\StoreAdapterContract;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Abstract base class for store adapters.
 */
abstract class StoreAdapter implements StoreAdapterContract
{
    protected ?string $lastError = null;

    protected int $timeout = 30;

    protected int $retries = 3;

    public function __construct(
        protected readonly HttpFactory $http,
        protected readonly CacheRepository $cache,
        protected readonly LoggerInterface $logger
    ) {}

    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
        ];
    }

    /**
     * Make HTTP request with retry logic.
     *
     * @param  array<string, string|int|float|array<string, string|int|float|bool|array|null>|bool|null>  $options
     * @return array<string, string|int|float|bool|array|null>|null
     */
    protected function makeRequest(string $url, array $options = []): ?array
    {
        $this->lastError = null;

        for ($attempt = 1; $attempt <= $this->retries; $attempt++) {
            try {
                $response = $this->http->timeout($this->timeout)
                    ->retry($this->retries, 100)
                    ->get($url, $options);

                if ($response->successful()) {
                    $data = $response->json();

                    return is_array($data) ? $data : null;
                }

                $this->lastError = "HTTP {$response->status()}: {$response->body()}";

                $this->logger->warning('Store adapter request failed', [
                    'store' => $this->getStoreName(),
                    'url' => $url,
                    'status' => $response->status(),
                    'attempt' => $attempt,
                ]);
            } catch (\Exception $exception) {
                $this->lastError = $exception->getMessage();

                $this->logger->error('Store adapter request exception', [
                    'store' => $this->getStoreName(),
                    'url' => $url,
                    'error' => $exception->getMessage(),
                    'attempt' => $attempt,
                ]);

                if ($attempt === $this->retries) {
                    return null;
                }

                sleep(1); // Wait before retry
            }
        }

        return null;
    }

    /**
     * Cache product data.
     *
     * @param  array<string, string|int|float|bool|array|null>  $data
     */
    protected function cacheProduct(string $identifier, array $data, int $ttl = 3600): void
    {
        $key = $this->getCacheKey($identifier);
        $this->cache->put($key, $data, $ttl);
    }

    /**
     * Get cached product data.
     *
     * @return array<string, string|int|float|bool|array|null>|null
     */
    protected function getCachedProduct(string $identifier): ?array
    {
        $key = $this->getCacheKey($identifier);
        $cached = $this->cache->get($key);

        return is_array($cached) ? $cached : null;
    }

    /**
     * Get cache key for product.
     */
    protected function getCacheKey(string $identifier): string
    {
        return "store_adapter:{$this->getStoreIdentifier()}:{$identifier}";
    }

    /**
     * Normalize product data to standard format.
     *
     * @param  array<string, string|int|float|bool|array|null>  $rawData
     * @return array<array|scalar|null>
     *
     * @psalm-return array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|scalar|null, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|scalar|null, brand: array|scalar|null, category: array|scalar|null, metadata: array|scalar}
     */
    protected function normalizeProductData(array $rawData): array
    {
        $price = $rawData['price'] ?? 0;
        $rating = $rawData['rating'] ?? null;
        $reviewsCount = $rawData['reviews_count'] ?? null;

        return [
            'name' => $rawData['name'] ?? '',
            'price' => is_numeric($price) ? (float) $price : 0.0,
            'currency' => $rawData['currency'] ?? 'USD',
            'url' => $rawData['url'] ?? '',
            'image_url' => $rawData['image_url'] ?? null,
            'availability' => $rawData['availability'] ?? 'unknown',
            'rating' => is_numeric($rating) ? (float) $rating : null,
            'reviews_count' => is_numeric($reviewsCount) ? (int) $reviewsCount : null,
            'description' => $rawData['description'] ?? null,
            'brand' => $rawData['brand'] ?? null,
            'category' => $rawData['category'] ?? null,
            'metadata' => $rawData['metadata'] ?? [],
        ];
    }
}
