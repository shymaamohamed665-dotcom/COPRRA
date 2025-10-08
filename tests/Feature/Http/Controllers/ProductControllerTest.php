<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Services\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    private \Mockery\MockInterface $productService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->productService = Mockery::mock(ProductService::class);
        $this->app->instance(ProductService::class, $this->productService);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function test_displays_index_page_with_products(): void
    {
        // Arrange
        $products = collect([Product::factory()->make()]);
        $this->productService->shouldReceive('getPaginatedProducts')
            ->once()
            ->andReturn($products);

        // Act
        $response = $this->get(route('products.index'));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products', $products);
    }
    public function test_displays_search_results_when_search_parameters_present(): void
    {
        // Arrange
        $query = 'laptop';
        $filters = ['category' => 'electronics'];
        $products = collect([Product::factory()->make()]);
        $this->productService->shouldReceive('searchProducts')
            ->with($query, Mockery::on(function ($arg) {
                return isset($arg['category_id']) && $arg['category_id'] === 'electronics';
            }))
            ->andReturn($products);

        // Act
        $response = $this->get(route('products.index', ['search' => $query, 'category' => 'electronics']));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('products.index');
        $response->assertViewHas('products', $products);
    }
    public function test_displays_product_show_page(): void
    {
        // Arrange
        $product = Product::factory()->create();
        $relatedProducts = collect([Product::factory()->make()]);
        $this->productService->shouldReceive('getBySlug')
            ->with($product->slug)
            ->andReturn($product);
        $this->productService->shouldReceive('getRelatedProducts')
            ->with($product)
            ->andReturn($relatedProducts);

        // Act
        $response = $this->get(route('products.show', $product->slug));

        // Assert
        $response->assertStatus(200);
        $response->assertViewIs('products.show');
        $response->assertViewHas('product', $product);
        $response->assertViewHas('relatedProducts', $relatedProducts);
    }
    public function test_returns_404_for_nonexistent_product(): void
    {
        // Arrange
        $this->productService->shouldReceive('getBySlug')
            ->with('nonexistent')
            ->andReturn(null);

        // Act
        $response = $this->get(route('products.show', 'nonexistent'));

        // Assert
        $response->assertStatus(404);
    }
    public function test_handles_search_with_sort_and_order(): void
    {
        // Arrange
        $query = 'phone';
        $products = collect([Product::factory()->make()]);
        $this->productService->shouldReceive('searchProducts')
            ->with($query, Mockery::on(function ($arg) {
                return isset($arg['sort_by']) && $arg['sort_by'] === 'price_asc';
            }))
            ->andReturn($products);

        // Act
        $response = $this->get(route('products.index', [
            'search' => $query,
            'sort' => 'price',
            'order' => 'asc',
        ]));

        // Assert
        $response->assertStatus(200);
        $response->assertViewHas('products', $products);
    }
}
