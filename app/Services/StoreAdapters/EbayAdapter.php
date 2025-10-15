<?php

declare(strict_types=1);

namespace App\Services\StoreAdapters;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Http\Client\Factory as HttpFactory;
use Psr\Log\LoggerInterface;

/**
 * eBay store adapter.
 */
final class EbayAdapter extends StoreAdapter
{
    private string $appId;

    public function __construct(HttpFactory $http, CacheRepository $cache, LoggerInterface $logger)
    {
        parent::__construct($http, $cache, $logger);
        $appId = config('services.ebay.app_id', '');
        $this->appId = is_string($appId) ? $appId : '';
    }

    /**
     * @psalm-return 'eBay'
     */
    #[\Override]
    public function getStoreName(): string
    {
        return 'eBay';
    }

    /**
     * @psalm-return 'ebay'
     */
    #[\Override]
    public function getStoreIdentifier(): string
    {
        return 'ebay';
    }

    #[\Override]
    public function isAvailable(): bool
    {
        return $this->appId !== null && $this->appId !== '';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function fetchProduct(string $productIdentifier): ?array
    {
        if (! $this->isAvailable()) {
            $this->lastError = 'eBay API credentials not configured';

            return null;
        }

        // Check cache first
        /** @var array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|null|scalar, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|null|scalar, brand: array|null|scalar, category: array|null|scalar, metadata: array|scalar}|null $cached */
        $cached = $this->getCachedProduct($productIdentifier);
        if ($cached) {
            return $cached;
        }

        $url = $this->buildApiUrl($productIdentifier);
        $response = $this->makeRequest($url, [
            'APPID' => $this->appId,
        ]);

        if ($response && isset($response['Item'])) {
            $item = $response['Item'];
            if (! is_array($item)) {
                return null;
            }
            if (! is_array($item)) {
                return null;
            }
            /** @var array<string, mixed> $item */
            $normalized = $this->normalizeEbayData($item);
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

        $url = 'https://svcs.ebay.com/services/search/FindingService/v1';
        $params = [
            'OPERATION-NAME' => 'findItemsAdvanced',
            'SERVICE-VERSION' => '1.0.0',
            'SECURITY-APPNAME' => $this->appId,
            'RESPONSE-DATA-FORMAT' => 'JSON',
            'keywords' => $query,
            'paginationInput.entriesPerPage' => 20,
            'paginationInput.pageNumber' => 1,
        ];

        $response = $this->makeRequest($url, $params);

        $searchResult = data_get($response, 'findItemsAdvancedResponse.0.searchResult.0');
        if (is_array($searchResult) && isset($searchResult['item'])) {
            $items = $searchResult['item'];

            if (! is_array($items)) {
                return [];
            }

            return array_values(array_filter(array_map(
                function ($item) {
                    if (! is_array($item)) {
                        return null;
                    }

                    /** @var array<string, array> $item */
                    return $this->normalizeEbaySearchResult($item);
                },
                $items
            )));
        }

        return [];
    }

    #[\Override]
    public function validateIdentifier(string $identifier): bool
    {
        // eBay item ID is numeric, typically 12 digits
        return preg_match('/^\d{10,15}$/', $identifier) === 1;
    }

    #[\Override]
    public function getProductUrl(string $identifier): string
    {
        return "https://www.ebay.com/itm/{$identifier}";
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function getRateLimits(): array
    {
        /** @var array<string, int> $rateLimits */
        return [
            'requests_per_minute' => 20,
            'requests_per_hour' => 1000,
            'requests_per_day' => 5000,
        ];
    }

    /**
     * Build API URL for product.
     *
     * @phpstan-ignore-next-line
     */
    private function buildApiUrl(string $itemId): string
    {
        return 'https://open.api.ebay.com/shopping?callname=GetSingleItem&responseencoding=JSON&ItemID='.$itemId.'&siteid=0&version=967';
    }

    /**
     * Normalize eBay product data.
     *
     * @param  array<string, array>  $item
     * @return (array|null|scalar)[]
     *
     * @phpstan-ignore-next-line
     *
     * @psalm-return array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|null|scalar, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|null|scalar, brand: array|null|scalar, category: array|null|scalar, metadata: array|scalar}
     */
    private function normalizeEbayData(array $item): array
    {
        $price = data_get($item, 'ConvertedCurrentPrice.Value', 0.0);

        return $this->normalizeProductData([
            'name' => data_get($item, 'Title', ''),
            'price' => is_numeric($price) ? (float) $price : 0.0,
            'currency' => data_get($item, 'ConvertedCurrentPrice.CurrencyID', 'USD'),
            'url' => data_get($item, 'ViewItemURLForNaturalSearch', ''),
            'image_url' => data_get($item, 'GalleryURL'),
            'availability' => $this->mapEbayAvailability(data_get($item, 'SellingStatus.SellingState')),
            'rating' => null, // Not directly available
            'reviews_count' => null, // Not directly available
            'description' => data_get($item, 'Description'),
            'brand' => null, // Can be extracted from item specifics
            'category' => data_get($item, 'PrimaryCategoryName'),
            'metadata' => [
                'item_id' => data_get($item, 'ItemID', ''),
                'listing_type' => data_get($item, 'ListingType'),
                'condition' => data_get($item, 'ConditionDisplayName'),
                'end_time' => data_get($item, 'EndTime'),
                'seller' => data_get($item, 'Seller.UserID'),
            ],
        ]);
    }

    /**
     * Map eBay availability status.
     *
     * @phpstan-ignore-next-line
     *
     * @psalm-return 'in_stock'|'out_of_stock'
     */
    private function mapEbayAvailability(?string $status): string
    {
        return match ($status) {
            'Active' => 'in_stock',
            'Ended' => 'out_of_stock',
            default => 'out_of_stock',
        };
    }

    /**
     * Normalize eBay search result item.
     *
     * @param  array<string, array>  $item
     * @return (array|null|scalar)[]
     *
     * @phpstan-ignore-next-line
     *
     * @psalm-return array{name: array|scalar, price: float, currency: array|scalar, url: array|scalar, image_url: array|null|scalar, availability: array|scalar, rating: float|null, reviews_count: int|null, description: array|null|scalar, brand: array|null|scalar, category: array|null|scalar, metadata: array|scalar}
     */
    private function normalizeEbaySearchResult(array $item): array
    {
        $price = data_get($item, 'sellingStatus.0.currentPrice.0.__value__', 0.0);

        return $this->normalizeProductData([
            'name' => data_get($item, 'title.0', ''),
            'price' => is_numeric($price) ? (float) $price : 0.0,
            'currency' => data_get($item, 'sellingStatus.0.currentPrice.0.@currencyId', 'USD'),
            'url' => data_get($item, 'viewItemURL.0', ''),
            'image_url' => data_get($item, 'galleryURL.0'),
            'availability' => 'in_stock', // Search results are typically for active items
            'rating' => null,
            'reviews_count' => null,
            'description' => null,
            'brand' => null,
            'category' => data_get($item, 'primaryCategory.0.categoryName.0'),
            'metadata' => [
                'item_id' => data_get($item, 'itemId.0', ''),
                'listing_type' => data_get($item, 'listingInfo.0.listingType.0'),
                'condition' => data_get($item, 'condition.0.conditionDisplayName.0'),
            ],
        ]);
    }
}
