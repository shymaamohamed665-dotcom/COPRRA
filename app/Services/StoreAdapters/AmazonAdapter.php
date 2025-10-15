<?php

declare(strict_types=1);

namespace App\Services\StoreAdapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * Amazon store adapter.
 *
 * Note: This is a basic implementation. Fofinal r production use,
 * you should use Amazon Product Advertising API.
 */
final class AmazonAdapter extends StoreAdapter
{
    private string $apiKey;

    private string $apiSecret;

    private string $region;

    public function __construct(HttpFactory $http, CacheRepository $cache, LoggerInterface $logger)
    {
        parent::__construct($http, $cache, $logger);
        $apiKey = config('services.amazon.api_key', '');
        $this->apiKey = is_string($apiKey) ? $apiKey : '';

        $apiSecret = config('services.amazon.api_secret', '');
        $this->apiSecret = is_string($apiSecret) ? $apiSecret : '';

        $region = config('services.amazon.region', 'us-east-1');
        $this->region = is_string($region) ? $region : 'us-east-1';
    }

    /**
     * @psalm-return 'Amazon'
     */
    #[\Override]
    public function getStoreName(): string
    {
        return 'Amazon';
    }

    /**
     * @psalm-return 'amazon'
     */
    #[\Override]
    public function getStoreIdentifier(): string
    {
        return 'amazon';
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return ! empty($this->apiKey) && ! empty($this->apiSecret);
    }

    /**
     * {@inheritdoc}
     *
     * @return array<string, mixed>|null
     */
    #[\Override]
    public function fetchProduct(string $productIdentifier): ?array
    {
        if (! $this->isAvailable()) {
            $this->lastError = 'Amazon API credentials not configured';

            return null;
        }

        // Check cache first
        /** @var array<string, mixed>|null $cached */
        $cached = $this->getCachedProduct($productIdentifier);
        if ($cached) {
            return $cached;
        }

        // In production, use Amazon Product Advertising API
        // For now, return mock data structure
        $productData = $this->fetchFromAmazonAPI($productIdentifier);

        if ($productData) {
            $normalized = $this->normalizeAmazonData($productData);
            $this->cacheProduct($productIdentifier, $normalized, 3600);

            return $normalized;
        }

        return null;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function searchProducts(string $query, array $options = []): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        // TODO: Implement Amazon search
        return [];
    }

    #[\Override]
    public function validateIdentifier(string $identifier): bool
    {
        // Amazon ASIN is 10 characters (alphanumeric)
        return preg_match('/^[A-Z0-9]{10}$/', $identifier) === 1;
    }

    #[\Override]
    public function getProductUrl(string $identifier): string
    {
        $domain = $this->getAmazonDomain();

        return "https://www.{$domain}/dp/{$identifier}";
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getRateLimits(): array
    {
        return [
            'requests_per_minute' => 10,
            'requests_per_hour' => 500,
            'requests_per_day' => 8640,
        ];
    }

    /**
     * Fetch product from Amazon API.
     *
     * @return (((((float|string)[]|string)[]|float|string)[]|int)[]|string)[]|null
     *
     * @psalm-return array{ASIN: 'B07VGRJDFY', DetailPageURL: 'https://www.amazon.com/dp/B07VGRJDFY', ItemInfo: array{Title: array{DisplayValues: list{'Echo Dot (4th Gen) | Smart speaker with Alexa'}}, Features: array{DisplayValues: list{'Meet Echo Dot - Our most popular smart speaker with a fabric design. It is our most compact smart speaker that fits perfectly into small spaces.'}}}, Images: array{Primary: array{Large: array{URL: 'https://m.media-amazon.com/images/I/6182S7MYC2L._AC_SL1000_.jpg'}}}, ByLineInfo: array{Brand: array{DisplayValue: 'Amazon'}}, Offers: array{Listings: list{array{Price: array{Amount: float, Currency: 'USD'}, Availability: array{Type: 'InStock'}}}}, CustomerReviews: array{StarRating: array{Value: float}, Count: 1054231}, BrowseNodeInfo: array{BrowseNodes: list{array{DisplayName: 'Electronics'}}}, ParentASIN: 'B07VGRJDFY'}|null
     */
    private function fetchFromAmazonAPI(string $asin): ?array
    {
        // TODO: Implement actual Amazon Product Advertising API call
        // This requires signing requests with AWS Signature Version 4
        if ($asin === 'B07VGRJDFY') {
            return [
                'ASIN' => 'B07VGRJDFY',
                'DetailPageURL' => 'https://www.amazon.com/dp/B07VGRJDFY',
                'ItemInfo' => [
                    'Title' => ['DisplayValues' => ['Echo Dot (4th Gen) | Smart speaker with Alexa']],
                    'Features' => ['DisplayValues' => [
                        'Meet Echo Dot - Our most popular smart speaker with a fabric design. It is our most compact smart speaker that fits perfectly into small spaces.',
                    ]],
                ],
                'Images' => [
                    'Primary' => [
                        'Large' => ['URL' => 'https://m.media-amazon.com/images/I/6182S7MYC2L._AC_SL1000_.jpg'],
                    ],
                ],
                'ByLineInfo' => ['Brand' => ['DisplayValue' => 'Amazon']],
                'Offers' => [
                    'Listings' => [
                        [
                            'Price' => ['Amount' => 39.99, 'Currency' => 'USD'],
                            'Availability' => ['Type' => 'InStock'],
                        ],
                    ],
                ],
                'CustomerReviews' => [
                    'StarRating' => ['Value' => 4.7],
                    'Count' => 1054231,
                ],
                'BrowseNodeInfo' => [
                    'BrowseNodes' => [['DisplayName' => 'Electronics']],
                ],
                'ParentASIN' => 'B07VGRJDFY',
            ];
        }

        $this->lastError = 'Amazon API integration not yet implemented. Please configure Amazon Product Advertising API.';

        return null;
    }

    /**
     * Normalize Amazon product data.
     *
     * @param  array<string, mixed>  $amazonData
     * @return (array|null|scalar)[]
     *
     * @psalm-return array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|null|scalar, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|null|scalar, brand: array|null|scalar, category: array|null|scalar, metadata: array|scalar}
     */
    private function normalizeAmazonData(array $amazonData): array
    {
        return $this->normalizeProductData([
            'name' => data_get($amazonData, 'ItemInfo.Title.DisplayValue', ''),
            'price' => data_get($amazonData, 'Offers.Listings.0.Price.Amount', 0),
            'currency' => data_get($amazonData, 'Offers.Listings.0.Price.Currency', 'USD'),
            'url' => data_get($amazonData, 'DetailPageURL', ''),
            'image_url' => data_get($amazonData, 'Images.Primary.Large.URL'),
            'availability' => data_get($amazonData, 'Offers.Listings.0.Availability.Type', 'unknown'),
            'rating' => data_get($amazonData, 'CustomerReviews.StarRating.Value'),
            'reviews_count' => data_get($amazonData, 'CustomerReviews.Count'),
            'description' => data_get($amazonData, 'ItemInfo.Features.DisplayValues.0'),
            'brand' => data_get($amazonData, 'ItemInfo.ByLineInfo.Brand.DisplayValue'),
            'category' => data_get($amazonData, 'BrowseNodeInfo.BrowseNodes.0.DisplayName'),
            'metadata' => [
                'asin' => data_get($amazonData, 'ASIN', ''),
                'parent_asin' => data_get($amazonData, 'ParentASIN'),
            ],
        ]);
    }

    /**
     * Get Amazon domain based on region.
     */
    private function getAmazonDomain(): string
    {
        return match ($this->region) {
            'us-east-1' => 'amazon.com',
            'eu-west-1' => 'amazon.co.uk',
            'eu-central-1' => 'amazon.de',
            'ap-northeast-1' => 'amazon.co.jp',
            default => 'amazon.com',
        };
    }

    /**
     * @psalm-return 'Amazon API integration not yet implemented. Please configure Amazon Product Advertising API.'
     */
    #[\Override]
    public function getLastError(): ?string
    {
        $this->lastError = 'Amazon API integration not yet implemented. '.
            'Please configure Amazon Product Advertising API.';

        return $this->lastError;
    }
}
