<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BrandTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_brand(): void
    {
        // Arrange
        $attributes = [
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'A test brand',
            'logo_url' => 'https://example.com/logo.png',
            'website_url' => 'https://example.com',
            'is_active' => true,
        ];

        // Act
        $brand = Brand::create($attributes);

        // Assert
        $this->assertInstanceOf(Brand::class, $brand);
        $this->assertEquals('Test Brand', $brand->name);
        $this->assertEquals('test-brand', $brand->slug);
        $this->assertTrue($brand->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_products_relationship(): void
    {
        // Arrange
        $brand = Brand::factory()->create();
        Product::factory()->create(['brand_id' => $brand->id]);

        // Act
        $brand->refresh();

        // Assert
        $this->assertCount(1, $brand->products);
        $this->assertInstanceOf(Product::class, $brand->products->first());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_validate_required_fields(): void
    {
        // Arrange
        $brand = new Brand;

        // Act
        $rules = $brand->getRules();

        // Assert
        $this->assertArrayHasKey('name', $rules);
        $this->assertEquals('required|string|max:255', $rules['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_validate_name_length(): void
    {
        // Arrange & Act
        $brand = Brand::factory()->make(['name' => str_repeat('a', 256)]);

        // Assert
        $this->assertFalse($brand->validate());
        $this->assertArrayHasKey('name', $brand->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_validate_website_url_format(): void
    {
        // Arrange & Act
        $brand = Brand::factory()->make(['website_url' => 'invalid-url']);

        // Assert
        $this->assertFalse($brand->validate());
        $this->assertArrayHasKey('website_url', $brand->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_validate_logo_url_format(): void
    {
        // Arrange & Act
        $brand = Brand::factory()->make(['logo_url' => 'invalid-url']);

        // Assert
        $this->assertFalse($brand->validate());
        $this->assertArrayHasKey('logo_url', $brand->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_scope_active_brands(): void
    {
        // Arrange
        Brand::factory()->create(['is_active' => true]);
        Brand::factory()->create(['is_active' => false]);

        // Act
        $activeBrands = Brand::active()->get();

        // Assert
        $this->assertCount(1, $activeBrands);
        $this->assertTrue($activeBrands->first()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_search_brands_by_name(): void
    {
        // Arrange
        Brand::factory()->create(['name' => 'Apple Brand']);
        Brand::factory()->create(['name' => 'Google Brand']);

        // Act
        $results = Brand::search('Apple')->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Apple Brand', $results->first()->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_get_brand_with_products_count(): void
    {
        // Arrange
        $brand = Brand::factory()->create();
        Product::factory()->count(3)->create(['brand_id' => $brand->id]);

        // Act
        $brandWithCount = Brand::withCount('products')->find($brand->id);

        // Assert
        $this->assertEquals(3, $brandWithCount->products_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_soft_delete_brand(): void
    {
        // Arrange
        $brand = Brand::factory()->create();

        // Act
        $brand->delete();

        // Assert
        $this->assertSoftDeleted($brand);
        $this->assertNull(Brand::find($brand->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_restore_soft_deleted_brand(): void
    {
        // Arrange
        $brand = Brand::factory()->create();
        $brand->delete();

        // Act
        $brand->restore();

        // Assert
        $this->assertNotSoftDeleted($brand);
        $this->assertNotNull(Brand::find($brand->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_slug_generation_on_create(): void
    {
        // Arrange & Act
        $brand = Brand::create(['name' => 'Test Brand Name']);

        // Assert
        $this->assertEquals('test-brand-name', $brand->slug);
    }
}
