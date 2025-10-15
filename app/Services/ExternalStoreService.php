<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use App\Models\Store;
use App\Services\StoreClients\StoreClientFactory;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

final class ExternalStoreService
{
    /** @var array<string, mixed> */
    private $storeConfigs;

    public function __construct()
    {
        $this->storeConfigs = Config::get('external_stores', []);
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array[]
     *
     * @psalm-return list<array<string, mixed>>
     */
    public function searchProducts(string $query, array $filters = []): array
    {
        $results = [];
        foreach (array_keys($this->storeConfigs) as $storeName) {
            try {
                $client = StoreClientFactory::create($storeName);
                if ($client) {
                    $storeResults = $client->search($query, $filters);
                    $results = array_merge($results, $this->normalizeProducts($storeResults, $storeName));
                }
            } catch (\Exception $e) {
                Log::error("Failed to search in {$storeName}", ['query' => $query, 'error' => $e->getMessage()]);
            }
        }

        return $this->sortAndFilterResults($results, $filters);
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getProductDetails(string $storeName, string $productId): ?array
    {
        return Cache::remember("external_product_{$storeName}_{$productId}", 3600, function () use ($storeName, $productId) {
            try {
                $client = StoreClientFactory::create($storeName);
                if ($client) {
                    $productData = $client->getProduct($productId);

                    return $productData ? $this->normalizeProductData($productData, $storeName) : null;
                }
            } catch (\Exception $e) {
                Log::error("Failed to get product details from {$storeName}", ['product_id' => $productId, 'error' => $e->getMessage()]);
            }

            return null;
        });
    }

    /**
     * @psalm-return int<0, max>
     */
    public function syncStoreProducts(string $storeName): int
    {
        $syncedCount = 0;
        try {
            $client = StoreClientFactory::create($storeName);
            if ($client) {
                $client->syncProducts(function ($productData) use ($storeName, &$syncedCount): void {
                    $this->syncProduct($productData, $storeName);
                    $syncedCount++;
                });
            }
        } catch (\Exception $e) {
            Log::error("Failed to sync products from {$storeName}", ['error' => $e->getMessage()]);
        }

        return $syncedCount;
    }

    /**
     * @return (float|int|null|string)[][]
     *
     * @psalm-return array<string, array<string, float|int|null|string>>
     */
    public function getStoreStatus(): array
    {
        $status = [];
        foreach (array_keys($this->storeConfigs) as $storeName) {
            try {
                $client = StoreClientFactory::create($storeName);
                $status[$storeName] = $client ? $client->getStatus() : ['status' => 'error', 'error' => 'Invalid configuration'];
                $status[$storeName]['last_check'] = now()->toISOString();
            } catch (\Exception $e) {
                $status[$storeName] = ['status' => 'error', 'error' => $e->getMessage(), 'last_check' => now()->toISOString()];
            }
        }

        return $status;
    }

    /**
     * @param  array<int, array<string, mixed>>  $products
     * @return (array|int|mixed|null|string)[][]
     *
     * @psalm-return array<int, array{external_id: mixed|null, name: ''|mixed, description: ''|mixed, price: 0|mixed, currency: 'USD'|mixed, image_url: ''|mixed, store_name: string, store_url: ''|mixed, rating: 0|mixed, reviews_count: 0|mixed, availability: 'in_stock'|mixed, shipping_info: array<never, never>|mixed, category: ''|mixed, brand: ''|mixed}>
     */
    private function normalizeProducts(array $products, string $storeName): array
    {
        return array_map(fn ($product) => $this->normalizeProductData($product, $storeName), $products);
    }

    /**
     * @param  array<string, mixed>  $productData
     * @return (array|int|mixed|null|string)[]
     *
     * @psalm-return array{external_id: mixed|null, name: ''|mixed, description: ''|mixed, price: 0|mixed, currency: 'USD'|mixed, image_url: ''|mixed, store_name: string, store_url: ''|mixed, rating: 0|mixed, reviews_count: 0|mixed, availability: 'in_stock'|mixed, shipping_info: array<never, never>|mixed, category: ''|mixed, brand: ''|mixed}
     */
    private function normalizeProductData(array $productData, string $storeName): array
    {
        return [
            'external_id' => $productData['id'] ?? null,
            'name' => $productData['title'] ?? $productData['name'] ?? '',
            'description' => $productData['description'] ?? '',
            'price' => $productData['price'] ?? 0,
            'currency' => $productData['currency'] ?? 'USD',
            'image_url' => $productData['image'] ?? $productData['thumbnail'] ?? '',
            'store_name' => $storeName,
            'store_url' => $productData['url'] ?? '',
            'rating' => $productData['rating'] ?? 0,
            'reviews_count' => $productData['reviews_count'] ?? 0,
            'availability' => $productData['availability'] ?? 'in_stock',
            'shipping_info' => $productData['shipping'] ?? [],
            'category' => $productData['category'] ?? '',
            'brand' => $productData['brand'] ?? '',
        ];
    }

    /**
     * @param  array<int, array<string, mixed>>  $results
     * @param  array<string, mixed>  $filters
     * @return array[]
     *
     * @psalm-return list<array<string, mixed>>
     */
    private function sortAndFilterResults(array $results, array $filters): array
    {
        if (isset($filters['sort_by']) && $filters['sort_by'] === 'price') {
            usort($results, fn ($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));
        }

        if (isset($filters['min_price'])) {
            $results = array_filter($results, fn ($product) => ($product['price'] ?? 0) >= $filters['min_price']);
        }

        if (isset($filters['max_price'])) {
            $results = array_filter($results, fn ($product) => ($product['price'] ?? 0) <= $filters['max_price']);
        }

        return array_values($results);
    }

    /**
     * @param  array<string, mixed>  $productData
     */
    private function syncProduct(array $productData, string $storeName): void
    {
        $normalizedData = $this->normalizeProductData($productData, $storeName);

        $store = Store::firstOrCreate(
            ['name' => $storeName],
            ['is_active' => true, 'api_config' => Config::get("external_stores.{$storeName}")]
        );

        Product::updateOrCreate(
            ['external_id' => $normalizedData['external_id'], 'store_id' => $store->id],
            [
                'name' => $normalizedData['name'],
                'description' => $normalizedData['description'],
                'price' => $normalizedData['price'],
                'currency' => $normalizedData['currency'],
                'image' => $normalizedData['image_url'],
                'rating' => $normalizedData['rating'],
                'reviews_count' => $normalizedData['reviews_count'],
                'is_active' => true,
                'external_data' => $normalizedData,
            ]
        );
    }
}
