<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Product;
use App\Models\Store;
use App\Services\ExternalStoreService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class ExternalStoreServiceTest extends TestCase
{
    private ExternalStoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ExternalStoreService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    /** @test */
    public function it_searches_products_across_all_stores(): void
    {
        // Arrange
        $query = 'laptop';
        $filters = ['category' => 'electronics'];
        $expectedResults = [
            ['name' => 'Laptop 1', 'store_name' => 'amazon'],
            ['name' => 'Laptop 2', 'store_name' => 'ebay'],
        ];

        // Mock Http for amazon
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.amazon.com/products/search', [
                'q' => $query,
                'limit' => 20,
                'filters' => $filters,
            ])
            ->andReturn(Mockery::mock(['successful' => true, 'json' => function () {
                return ['products' => [['title' => 'Laptop 1', 'id' => 1]]];
            }]));

        // Mock for ebay
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.ebay.com/buy/browse/v1/search', [
                'q' => $query,
                'limit' => 20,
                'filters' => $filters,
            ])
            ->andReturn(Mockery::mock(['successful' => true, 'json' => function () {
                return ['products' => [['title' => 'Laptop 2', 'id' => 2]]];
            }]));

        // Mock for aliexpress
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.aliexpress.com/products/search', [
                'q' => $query,
                'limit' => 20,
                'filters' => $filters,
            ])
            ->andReturn(Mockery::mock(['successful' => true, 'json' => function () {
                return ['products' => []];
            }]));

        // Act
        $results = $this->service->searchProducts($query, $filters);

        // Assert
        $this->assertIsArray($results);
        $this->assertCount(2, $results);
        $this->assertEquals('Laptop 1', $results[0]['name']);
        $this->assertEquals('amazon', $results[0]['store_name']);
    }

    /** @test */
    public function it_handles_api_failure_gracefully(): void
    {
        // Arrange
        $query = 'laptop';
        $filters = [];

        // Mock Http to fail for amazon
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.amazon.com/products/search', Mockery::any())
            ->andThrow(new \Exception('API Error'));

        // Mock success for others
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.ebay.com/buy/browse/v1/search', Mockery::any())
            ->andReturn(Mockery::mock(['successful' => true, 'json' => function () {
                return ['products' => [['title' => 'Laptop', 'id' => 1]]];
            }]));

        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.aliexpress.com/products/search', Mockery::any())
            ->andReturn(Mockery::mock(['successful' => true, 'json' => function () {
                return ['products' => []];
            }]));

        // Mock Log
        Log::shouldReceive('error')->once();

        // Act
        $results = $this->service->searchProducts($query, $filters);

        // Assert
        $this->assertIsArray($results);
        $this->assertCount(1, $results);
    }

    /** @test */
    public function it_gets_product_details_with_cache(): void
    {
        // Arrange
        $storeName = 'amazon';
        $productId = '123';
        $cacheKey = 'external_product_amazon_123';
        $expectedData = ['name' => 'Product', 'price' => 100];

        Cache::shouldReceive('remember')
            ->with($cacheKey, 3600, Mockery::type('Closure'))
            ->andReturn($expectedData);

        // Act
        $result = $this->service->getProductDetails($storeName, $productId);

        // Assert
        $this->assertEquals($expectedData, $result);
    }

    /** @test */
    public function it_returns_null_for_invalid_store(): void
    {
        // Arrange
        $storeName = 'invalid';
        $productId = '123';

        // Act
        $result = $this->service->getProductDetails($storeName, $productId);

        // Assert
        $this->assertNull($result);
    }

    /** @test */
    public function it_syncs_store_products(): void
    {
        // Arrange
        $storeName = 'amazon';
        $productsData = [
            ['id' => 1, 'title' => 'Product 1', 'price' => 100],
            ['id' => 2, 'title' => 'Product 2', 'price' => 200],
        ];

        // Mock Http
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.amazon.com/products', ['page' => 1, 'limit' => 100])
            ->andReturn(Mockery::mock(['successful' => true, 'json' => function () use ($productsData) {
                return ['products' => $productsData, 'has_more' => false];
            }]));

        // Mock Store and Product creation
        Store::shouldReceive('firstOrCreate')
            ->andReturn(Mockery::mock(Store::class, ['id' => 1]));
        Product::shouldReceive('updateOrCreate')
            ->twice();

        // Act
        $syncedCount = $this->service->syncStoreProducts($storeName);

        // Assert
        $this->assertEquals(2, $syncedCount);
    }

    /** @test */
    public function it_gets_store_status(): void
    {
        // Arrange
        $storeName = 'amazon';

        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.amazon.com/products/health')
            ->andReturn(Mockery::mock([
                'successful' => true,
                'transferStats' => Mockery::mock(['getHandlerStat' => 0.5]),
            ]));

        // Similar for others
        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.ebay.com/buy/browse/v1/health')
            ->andReturn(Mockery::mock([
                'successful' => false,
            ]));

        Http::shouldReceive('withHeaders->timeout->get')
            ->with('https://api.aliexpress.com/products/health')
            ->andReturn(Mockery::mock([
                'successful' => true,
                'transferStats' => Mockery::mock(['getHandlerStat' => 1.0]),
            ]));

        // Act
        $status = $this->service->getStoreStatus();

        // Assert
        $this->assertIsArray($status);
        $this->assertArrayHasKey('amazon', $status);
        $this->assertArrayHasKey('ebay', $status);
        $this->assertArrayHasKey('aliexpress', $status);
        $this->assertEquals('online', $status['amazon']['status']);
        $this->assertEquals('offline', $status['ebay']['status']);
    }
}
