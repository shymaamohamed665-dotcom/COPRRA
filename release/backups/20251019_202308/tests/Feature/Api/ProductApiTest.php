<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_list_products(): void
    {
        Product::factory()->count(5)->create(['is_active' => true]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(5, 'data');
    }

    public function test_can_search_products_by_name(): void
    {
        Product::factory()->create(['name' => 'iPhone 15 Pro', 'is_active' => true]);
        Product::factory()->create(['name' => 'Samsung Galaxy', 'is_active' => true]);

        $response = $this->getJson('/api/products?search=iPhone');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.name', 'iPhone 15 Pro');
    }

    public function test_can_filter_products_by_category(): void
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create([
            'category_id' => $category->id,
            'is_active' => true,
        ]);
        Product::factory()->count(2)->create(['is_active' => true]);

        $response = $this->getJson("/api/products?category_id={$category->id}");

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_filter_products_by_brand(): void
    {
        $brand = Brand::factory()->create();
        Product::factory()->count(2)->create([
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);
        Product::factory()->count(3)->create(['is_active' => true]);

        $response = $this->getJson("/api/products?brand_id={$brand->id}");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_can_filter_products_by_price_range(): void
    {
        Product::factory()->create(['price' => 50.00, 'is_active' => true]);
        Product::factory()->create(['price' => 150.00, 'is_active' => true]);
        Product::factory()->create(['price' => 250.00, 'is_active' => true]);

        $response = $this->getJson('/api/products?min_price=100&max_price=200');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_can_filter_featured_products(): void
    {
        Product::factory()->count(3)->create([
            'is_featured' => true,
            'is_active' => true,
        ]);
        Product::factory()->count(2)->create([
            'is_featured' => false,
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/products?is_featured=1');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_can_sort_products_by_price_ascending(): void
    {
        Product::factory()->create(['price' => 300.00, 'is_active' => true]);
        Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        Product::factory()->create(['price' => 200.00, 'is_active' => true]);

        $response = $this->getJson('/api/products?sort=price_asc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(100.00, $data[0]['price']);
        $this->assertEquals(200.00, $data[1]['price']);
        $this->assertEquals(300.00, $data[2]['price']);
    }

    public function test_can_sort_products_by_price_descending(): void
    {
        Product::factory()->create(['price' => 100.00, 'is_active' => true]);
        Product::factory()->create(['price' => 300.00, 'is_active' => true]);
        Product::factory()->create(['price' => 200.00, 'is_active' => true]);

        $response = $this->getJson('/api/products?sort=price_desc');

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertEquals(300.00, $data[0]['price']);
        $this->assertEquals(200.00, $data[1]['price']);
        $this->assertEquals(100.00, $data[2]['price']);
    }

    public function test_inactive_products_are_not_listed(): void
    {
        Product::factory()->count(3)->create(['is_active' => true]);
        Product::factory()->count(2)->create(['is_active' => false]);

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_product_list_is_paginated(): void
    {
        Product::factory()->count(25)->create(['is_active' => true]);

        $response = $this->getJson('/api/products?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data')
            ->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_view_single_product(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_product_response_includes_relationships(): void
    {
        $category = Category::factory()->create();
        $brand = Brand::factory()->create();
        $product = Product::factory()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'is_active' => true,
        ]);

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'price',
                    'category' => ['id', 'name'],
                    'brand' => ['id', 'name'],
                ],
            ]);
    }

    public function test_per_page_is_limited_to_100(): void
    {
        Product::factory()->count(150)->create(['is_active' => true]);

        $response = $this->getJson('/api/products?per_page=200');

        $response->assertStatus(200);
        $this->assertLessThanOrEqual(100, count($response->json('data')));
    }
}
