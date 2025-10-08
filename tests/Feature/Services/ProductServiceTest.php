<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Product;
use App\Repositories\ProductRepository;
use App\Services\CacheService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Tests\TestCase;

class ProductServiceTest extends TestCase
{
    private ProductService $service;

    private \Mockery\MockInterface $repository;

    private \Mockery\MockInterface $cache;

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = Mockery::mock(ProductRepository::class);
        $this->cache = Mockery::mock(CacheService::class);
        $this->service = new ProductService($this->repository, $this->cache);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function test_returns_paginated_products_from_cache(): void
    {
        // Arrange
        $perPage = 15;
        $page = 1;
        $products = collect([
            Product::factory()->make(['id' => 1, 'name' => 'Product 1']),
            Product::factory()->make(['id' => 2, 'name' => 'Product 2']),
        ]);

        $paginator = new LengthAwarePaginator(
            $products,
            2,
            $perPage,
            $page,
            ['path' => 'http://localhost', 'pageName' => 'page']
        );

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, Mockery::type('Closure'), ['products'])
            ->andReturn($paginator);

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(2, $result->total());
        $this->assertEquals($perPage, $result->perPage());
    }
    public function test_returns_empty_paginator_when_cache_returns_null(): void
    {
        // Arrange
        $perPage = 15;
        $page = 1;

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, Mockery::type('Closure'), ['products'])
            ->andReturn(null);

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
        $this->assertEquals($perPage, $result->perPage());
    }
    public function test_handles_invalid_page_number(): void
    {
        // Arrange
        $perPage = 15;
        $invalidPage = 'invalid';

        // Mock request to return invalid page
        $request = Mockery::mock(Request::class);
        $request->shouldReceive('get')->with('page', 1)->andReturn($invalidPage);
        $request->shouldReceive('url')->andReturn('http://localhost');
        $this->app->instance('request', $request);

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, Mockery::type('Closure'), ['products'])
            ->andReturn(null);

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(0, $result->total());
    }
    public function test_uses_default_per_page_when_not_specified(): void
    {
        // Arrange
        $defaultPerPage = 15;
        $page = 1;

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, Mockery::type('Closure'), ['products'])
            ->andReturn(null);

        // Act
        $result = $this->service->getPaginatedProducts();

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals($defaultPerPage, $result->perPage());
    }
    public function test_calls_repository_when_cache_miss(): void
    {
        // Arrange
        $perPage = 15;
        $page = 1;
        $products = collect([
            Product::factory()->make(['id' => 1, 'name' => 'Product 1']),
        ]);

        $paginator = new LengthAwarePaginator(
            $products,
            1,
            $perPage,
            $page,
            ['path' => 'http://localhost', 'pageName' => 'page']
        );

        $this->cache->shouldReceive('remember')
            ->once()
            ->with('products.page.1', 3600, Mockery::type('Closure'), ['products'])
            ->andReturnUsing(function ($key, $ttl, $callback) {
                return $callback();
            });

        $this->repository->shouldReceive('getPaginatedActive')
            ->once()
            ->with($perPage)
            ->andReturn($paginator);

        // Act
        $result = $this->service->getPaginatedProducts($perPage);

        // Assert
        $this->assertInstanceOf(LengthAwarePaginator::class, $result);
        $this->assertEquals(1, $result->total());
    }
}
