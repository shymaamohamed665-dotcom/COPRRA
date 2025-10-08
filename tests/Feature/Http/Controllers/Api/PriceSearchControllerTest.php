<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use App\Models\Brand;
use App\Models\Category;
use App\Models\PriceOffer;
use App\Models\Product;
use App\Models\Store;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PriceSearchControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test data
        $brand = Brand::factory()->create();
        $category = Category::factory()->create();
        $store = Store::factory()->create();

        $product = Product::factory()->create([
            'brand_id' => $brand->id,
            'category_id' => $category->id,
            'is_active' => true,
        ]);

        PriceOffer::factory()->create([
            'product_id' => $product->id,
            'store_id' => $store->id,
            'price' => 100.00,
            'is_available' => true,
        ]);
    }
    public function test_can_get_best_offer_by_product_id()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/best-offer', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'price',
                    'store_id',
                    'store',
                    'store_url',
                    'is_available',
                    'total_offers',
                    'offers' => [
                        '*' => [
                            'id',
                            'price',
                            'store_id',
                            'store',
                            'store_url',
                            'is_available',
                        ],
                    ],
                ],
            ]);
    }
    public function test_can_get_best_offer_by_product_name()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/best-offer', [
            'product_name' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'product_id',
                    'price',
                    'store_id',
                    'store',
                    'store_url',
                    'is_available',
                    'total_offers',
                ],
            ]);
    }
    public function test_returns_404_for_nonexistent_product_by_id()
    {
        $response = $this->getJson('/api/price-search/best-offer', [
            'product_id' => 99999,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Product not found',
            ]);
    }
    public function test_returns_404_for_nonexistent_product_by_name()
    {
        $response = $this->getJson('/api/price-search/best-offer', [
            'product_name' => 'Nonexistent Product',
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'Product not found',
            ]);
    }
    public function test_returns_404_when_no_offers_available()
    {
        $product = Product::factory()->create(['is_active' => true]);

        // Don't create any price offers for this product

        $response = $this->getJson('/api/price-search/best-offer', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No offers available for this product',
            ]);
    }
    public function test_returns_all_products_when_no_parameters_provided()
    {
        $response = $this->getJson('/api/price-search/best-offer');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'product_id',
                        'name',
                        'price',
                        'store',
                        'is_available',
                    ],
                ],
            ]);
    }
    public function test_returns_404_when_no_products_exist()
    {
        // Delete all products
        Product::query()->delete();

        $response = $this->getJson('/api/price-search/best-offer');

        $response->assertStatus(404)
            ->assertJson([
                'message' => 'No products available',
            ]);
    }
    public function test_can_get_supported_stores()
    {
        $response = $this->getJson('/api/price-search/supported-stores');

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'name',
                    'slug',
                    'is_active',
                ],
            ]);
    }
    public function test_can_search_products()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/search', [
            'q' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_can_search_products_with_query_parameter()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/search', [
            'query' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_can_search_products_with_name_parameter()
    {
        $product = Product::first();

        $response = $this->getJson('/api/price-search/search', [
            'name' => $product->name,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_returns_400_when_search_query_is_empty()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_returns_400_when_search_query_is_missing()
    {
        $response = $this->getJson('/api/price-search/search');

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_special_characters()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'test@#$%^&*()',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_sql_injection_attempt()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => "'; DROP TABLE products; --",
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_xss_attempt()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '<script>alert("xss")</script>',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_very_long_query()
    {
        $longQuery = str_repeat('a', 1000);

        $response = $this->getJson('/api/price-search/search', [
            'q' => $longQuery,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_unicode_characters()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '测试产品 🛍️',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_multiple_words()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'test product search',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_numbers()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '12345',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_mixed_content()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'Product 123 @#$% test',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_empty_results()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 'nonexistentproductname',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);

        $data = $response->json('data');
        $this->assertIsArray($data);
        $this->assertEmpty($data);
    }
    public function test_handles_search_with_whitespace_only()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => '   ',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_tabs_and_newlines()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => "\t\n\r",
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_null_parameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => null,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_array_parameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => ['test', 'product'],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_object_parameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => (object) ['test' => 'product'],
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_boolean_parameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => true,
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Search query is required. Use parameter: q, query, or name',
            ]);
    }
    public function test_handles_search_with_numeric_parameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 123,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
    public function test_handles_search_with_float_parameter()
    {
        $response = $this->getJson('/api/price-search/search', [
            'q' => 123.45,
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data',
                'results',
                'products',
                'total',
                'query',
            ]);
    }
}
