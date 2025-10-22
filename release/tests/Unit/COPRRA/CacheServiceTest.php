<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Services\CacheService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CacheService::class)]
class CacheServiceTest extends TestCase
{
    use RefreshDatabase;

    protected CacheService $cacheService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->cacheService = new CacheService;
        Cache::flush();
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }

    public function test_it_generates_correct_product_cache_key(): void
    {
        $key = $this->cacheService->getProductKey(123);

        $this->assertEquals('product:123', $key);
    }

    public function test_it_generates_correct_category_cache_key(): void
    {
        $key = $this->cacheService->getCategoryKey(456);

        $this->assertEquals('category:456', $key);
    }

    public function test_it_generates_correct_store_cache_key(): void
    {
        $key = $this->cacheService->getStoreKey(789);

        $this->assertEquals('store:789', $key);
    }

    public function test_it_generates_correct_price_comparison_cache_key(): void
    {
        $key = $this->cacheService->getPriceComparisonKey(123);

        $this->assertEquals('price_comparison:123', $key);
    }

    public function test_it_generates_correct_exchange_rate_cache_key(): void
    {
        $key = $this->cacheService->getExchangeRateKey('USD', 'EUR');

        $this->assertEquals('exchange_rate:USD_EUR', $key);
    }

    public function test_it_generates_correct_search_cache_key(): void
    {
        $key = $this->cacheService->getSearchKey('laptop');

        $this->assertStringStartsWith('search:', $key);
        $this->assertStringContainsString(md5('laptop'), $key);
    }

    public function test_it_generates_search_cache_key_with_filters(): void
    {
        $filters = ['category' => 'electronics', 'price_min' => 100];
        $key = $this->cacheService->getSearchKey('laptop', $filters);

        $this->assertStringStartsWith('search:', $key);
        $this->assertStringContainsString(md5('laptop'), $key);
    }

    public function test_it_caches_product_data(): void
    {
        $productData = ['id' => 123, 'name' => 'Test Product'];

        $result = $this->cacheService->cacheProduct(123, $productData);

        $this->assertTrue($result);

        $cached = $this->cacheService->getCachedProduct(123);
        $this->assertEquals($productData, $cached);
    }

    public function test_it_caches_price_comparison_data(): void
    {
        $priceData = [
            'product_id' => 123,
            'prices' => [
                ['store' => 'Store A', 'price' => 100.00],
                ['store' => 'Store B', 'price' => 95.00],
            ],
        ];

        $result = $this->cacheService->cachePriceComparison(123, $priceData);

        $this->assertTrue($result);

        $cached = $this->cacheService->getCachedPriceComparison(123);
        $this->assertEquals($priceData, $cached);
    }

    public function test_it_caches_search_results(): void
    {
        $searchResults = [
            ['id' => 1, 'name' => 'Product 1'],
            ['id' => 2, 'name' => 'Product 2'],
        ];

        $result = $this->cacheService->cacheSearchResults('laptop', [], $searchResults);

        $this->assertTrue($result);

        $cached = $this->cacheService->getCachedSearchResults('laptop');
        $this->assertEquals($searchResults, $cached);
    }

    public function test_it_invalidates_product_cache(): void
    {
        $productData = ['id' => 123, 'name' => 'Test Product'];
        $this->cacheService->cacheProduct(123, $productData);

        $this->assertNotNull($this->cacheService->getCachedProduct(123));

        $this->cacheService->invalidateProduct(123);

        $this->assertNull($this->cacheService->getCachedProduct(123));
    }

    public function test_it_invalidates_category_cache(): void
    {
        $key = $this->cacheService->getCategoryKey(456);
        Cache::put($key, ['id' => 456, 'name' => 'Test Category'], 3600);

        $this->assertNotNull(Cache::get($key));

        $this->cacheService->invalidateCategory(456);

        $this->assertNull(Cache::get($key));
    }

    public function test_it_invalidates_store_cache(): void
    {
        $key = $this->cacheService->getStoreKey(789);
        Cache::put($key, ['id' => 789, 'name' => 'Test Store'], 3600);

        $this->assertNotNull(Cache::get($key));

        $this->cacheService->invalidateStore(789);

        $this->assertNull(Cache::get($key));
    }

    public function test_it_returns_null_for_non_existent_cache(): void
    {
        $cached = $this->cacheService->getCachedProduct(999);

        $this->assertNull($cached);
    }

    public function test_it_returns_cache_statistics(): void
    {
        $stats = $this->cacheService->getStatistics();

        $this->assertIsArray($stats);
        $this->assertArrayHasKey('driver', $stats);
        $this->assertArrayHasKey('prefixes', $stats);
        $this->assertArrayHasKey('durations', $stats);
    }

    public function test_it_uses_remember_method_correctly(): void
    {
        $key = 'test_remember_key';
        $value = 'test_value';

        $result = $this->cacheService->remember($key, 3600, function () use ($value) {
            return $value;
        });

        $this->assertEquals($value, $result);

        // Second call should return cached value
        $result2 = $this->cacheService->remember($key, 3600, function () {
            return 'different_value';
        });

        $this->assertEquals($value, $result2);
    }

    public function test_it_clears_all_cache(): void
    {
        // Add some cache entries
        $this->cacheService->cacheProduct(1, ['name' => 'Product 1']);
        $this->cacheService->cacheProduct(2, ['name' => 'Product 2']);

        $this->assertNotNull($this->cacheService->getCachedProduct(1));
        $this->assertNotNull($this->cacheService->getCachedProduct(2));

        // Clear all cache
        $this->cacheService->clearAll();

        $this->assertNull($this->cacheService->getCachedProduct(1));
        $this->assertNull($this->cacheService->getCachedProduct(2));
    }

    public function test_it_respects_custom_cache_duration(): void
    {
        $productData = ['id' => 123, 'name' => 'Test Product'];
        $customDuration = 60; // 1 minute

        $this->cacheService->cacheProduct(123, $productData, $customDuration);

        $cached = $this->cacheService->getCachedProduct(123);
        $this->assertEquals($productData, $cached);
    }

    public function test_it_handles_search_with_different_filters(): void
    {
        $results1 = [['id' => 1]];
        $results2 = [['id' => 2]];

        $filters1 = ['category' => 'electronics'];
        $filters2 = ['category' => 'books'];

        $this->cacheService->cacheSearchResults('laptop', $filters1, $results1);
        $this->cacheService->cacheSearchResults('laptop', $filters2, $results2);

        $cached1 = $this->cacheService->getCachedSearchResults('laptop', $filters1);
        $cached2 = $this->cacheService->getCachedSearchResults('laptop', $filters2);

        $this->assertEquals($results1, $cached1);
        $this->assertEquals($results2, $cached2);
        $this->assertNotEquals($cached1, $cached2);
    }
}
