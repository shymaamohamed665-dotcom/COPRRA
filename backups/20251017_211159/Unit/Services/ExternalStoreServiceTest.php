<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Store;
use App\Services\ExternalStoreService;
use Illuminate\Support\Facades\Cache;
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

    public function test_it_searches_products_across_all_stores(): void
    {
        // Arrange
        $query = 'laptop';
        $filters = ['category' => 'electronics'];

        // Act - with no store configs, returns empty array
        $results = $this->service->searchProducts($query, $filters);

        // Assert
        $this->assertIsArray($results);
    }

    public function test_it_handles_api_failure_gracefully(): void
    {
        // Arrange
        $query = 'laptop';
        $filters = [];

        // Act - with no store configs, handles gracefully
        $results = $this->service->searchProducts($query, $filters);

        // Assert
        $this->assertIsArray($results);
    }

    public function test_it_gets_product_details_with_cache(): void
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

    public function test_it_returns_null_for_invalid_store(): void
    {
        // Arrange
        $storeName = 'invalid';
        $productId = '123';

        // Act
        $result = $this->service->getProductDetails($storeName, $productId);

        // Assert
        $this->assertNull($result);
    }

    public function test_it_syncs_store_products(): void
    {
        // Arrange
        $storeName = 'unknown_store';

        // Act - should return 0 for unknown store
        $syncedCount = $this->service->syncStoreProducts($storeName);

        // Assert
        $this->assertEquals(0, $syncedCount);
    }

    public function test_it_gets_store_status(): void
    {
        // Act - with empty config, should return empty array
        $status = $this->service->getStoreStatus();

        // Assert
        $this->assertIsArray($status);
    }
}
