<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ProductControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }

    public function test_can_list_products()
    {
        $products = Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                        'description',
                        'created_at',
                        'updated_at',
                        'category',
                        'brand',
                        'stores',
                    ],
                ],
                'links',
                'meta',
            ])
            ->assertJsonCount(3, 'data')
            ->assertJsonFragment([
                'id' => $products->first()->id,
                'name' => $products->first()->name,
            ]);
    }

    public function test_can_show_specific_product()
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'name',
                    'slug',
                    'price',
                    'description',
                    'created_at',
                    'updated_at',
                    'category',
                    'brand',
                    'stores',
                ],
            ])
            ->assertJson([
                'data' => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => $product->price,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_product()
    {
        $response = $this->getJson('/api/products/999');

        $response->assertStatus(404);
    }

    public function test_can_search_products()
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $response = $this->getJson('/api/products', [
            'search' => 'Test',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
            ]);
    }

    public function test_can_filter_products_by_category()
    {
        $product = Product::factory()->create();

        $response = $this->getJson('/api/products', [
            'category_id' => $product->category_id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
            ]);
    }

    public function test_can_sort_products()
    {
        $response = $this->getJson('/api/products', [
            'sort' => 'price_asc',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'price',
                    ],
                ],
            ]);
    }

    public function test_can_paginate_products()
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/products', [
            'per_page' => 10,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'links',
                'meta',
            ]);
    }

    public function test_handles_invalid_pagination_parameters()
    {
        $response = $this->getJson('/api/products?per_page=invalid');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['per_page']);
    }

    public function test_handles_invalid_sort_parameters()
    {
        $response = $this->getJson('/api/products?sort=invalid_sort');

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['sort']);
    }
}
