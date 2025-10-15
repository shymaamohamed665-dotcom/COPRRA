<?php

declare(strict_types=1);

namespace App\Services\StoreAdapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Noon store adapter (Middle East e-commerce).
 */
final class NoonAdapter extends StoreAdapter
{
    private string $apiKey;

    private string $country;

    public function __construct(HttpFactory $http, CacheRepository $cache, LoggerInterface $logger)
    {
        parent::__construct($http, $cache, $logger);
        $apiKey = config()->get('services.noon.api_key');
        $this->apiKey = is_string($apiKey) ? $apiKey : '';

        $country = config()->get('services.noon.country');
        $this->country = is_string($country) ? $country : 'ae'; // ae, sa, eg
    }

    /**
     * @psalm-return 'Noon'
     */
    #[\Override]
    public function getStoreName(): string
    {
        return 'Noon';
    }

    /**
     * @psalm-return 'noon'
     */
    #[\Override]
    public function getStoreIdentifier(): string
    {
        return 'noon';
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return $this->apiKey !== null && $this->apiKey !== '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function fetchProduct(string $productIdentifier): ?array
    {
        if (! $this->isAvailable()) {
            $this->lastError = 'Noon API key not configured';

            return null;
        }

        // Check cache first
        /** @var array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|null|scalar, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|null|scalar, brand: array|null|scalar, category: array|null|scalar, metadata: array|scalar}|null $cached */
        $cached = $this->getCachedProduct($productIdentifier);
        if (is_array($cached)) {
            return $cached;
        }

        $url = $this->buildApiUrl($productIdentifier);
        /** @var array<string, array>|null $response */
        $response = $this->makeRequest($url, [
            'api_key' => $this->apiKey,
        ]);

        if ($response && isset($response['product'])) {
            /** @var array<string, array> $product */
            $product = $response['product'];
            $normalized = $this->normalizeNoonData($product);
            $this->cacheProduct($productIdentifier, $normalized, 3600);

            return $normalized;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     *
     * @return array<int, array<string, null|scalar|array>>
     *
     * @psalm-return list<non-empty-array<string, null|scalar|array>>
     */
    #[\Override]
    public function searchProducts(string $query, array $options = []): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $url = 'https://api.noon.com/v1/search';

        $params = [
            'q' => $query,
            'api_key' => $this->apiKey,
            'limit' => 20,
            'page' => 1,
        ];

        /** @var array<string, array>|null $response */
        $response = $this->makeRequest($url, $params);

        if ($response && isset($response['products'])) {
            /** @var array<array<string, array>> $products */
            $products = $response['products'];

            return array_values(array_map(
                fn (array $product) => $this->normalizeNoonData($product),
                $products
            ));
        }

        return [];
    }

    #[\Override]
    public function validateIdentifier(string $identifier): bool
    {
        // Noon SKU format: N followed by numbers
        return preg_match('/^N\d+$/', $identifier) === 1;
    }

    #[\Override]
    public function getProductUrl(string $identifier): string
    {
        $domain = $this->getNoonDomain();

        return "https://www.{$domain}/product/{$identifier}";
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 30,
            'requests_per_hour' => 1000,
            'requests_per_day' => 10000,
        ];
    }

    /**
     * Build API URL for product.
     *
     * @phpstan-ignore-next-line
     */
    private function buildApiUrl(string $sku): string
    {
        return "https://api.noon.com/v1/products/{$sku}";
    }

    /**
     * Normalize Noon product data.
     *
     * @param  array<string, array<string, string|int|float|bool|array|null>|string|int|float|bool|null>  $noonData
     * @return (array|null|scalar)[]
     *
     * @phpstan-ignore-next-line
     *
     * @psalm-return array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|null|scalar, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|null|scalar, brand: array|null|scalar, category: array|null|scalar, metadata: array|scalar}
     */
    private function normalizeNoonData(array $noonData): array
    {
        $price = $noonData['sale_price'] ?? $noonData['price'] ?? 0;
        $sku = is_string($noonData['sku'] ?? null) ? $noonData['sku'] : '';

        return $this->normalizeProductData([
            'name' => $noonData['name'] ?? '',
            'price' => is_numeric($price) ? (float) $price : 0.0,
            'currency' => $this->getCurrency(),
            'url' => $noonData['url'] ?? $this->getProductUrl($sku),
            'image_url' => $noonData['image_url'] ?? null,
            'availability' => $noonData['in_stock'] ?? false ? 'in_stock' : 'out_of_stock',
            'rating' => $noonData['rating'] ?? null,
            'reviews_count' => $noonData['reviews_count'] ?? null,
            'description' => $noonData['description'] ?? null,
            'brand' => $noonData['brand'] ?? null,
            'category' => $noonData['category'] ?? null,
            'metadata' => [
                'sku' => $noonData['sku'] ?? '',
                'seller' => $noonData['seller'] ?? null,
                'discount_percentage' => $noonData['discount_percentage'] ?? null,
            ],
        ]);
    }

    /**
     * Get currency based on country.
     *
     * @phpstan-ignore-next-line
     *
     * @psalm-return 'AED'|'EGP'|'SAR'
     */
    private function getCurrency(): string
    {
        return match ($this->country) {
            'ae' => 'AED',
            'sa' => 'SAR',
            'eg' => 'EGP',
            default => 'AED',
        };
    }

    /**
     * Get Noon domain based on country.
     *
     * @phpstan-ignore-next-line
     */
    private function getNoonDomain(): string
    {
        return match ($this->country) {
            'ae' => 'noon.com/uae-en',
            'sa' => 'noon.com/saudi-en',
            'eg' => 'noon.com/egypt-en',
            default => 'noon.com',
        };
    }
}
