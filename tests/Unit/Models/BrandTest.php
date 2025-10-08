<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Brand;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * Unit tests for the Brand model.
 *
 * @covers \App\Models\Brand
 */

/**
 * @runTestsInSeparateProcesses
 */
class BrandTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = $this->faker();
    }

    /**
     * Test that products relation is a HasMany instance.
     */
    public function test_products_relation(): void
    {
        $brand = new Brand;

        $relation = $brand->products();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive adds where clause for is_active.
     */
    public function test_scope_active(): void
    {
        $query = Brand::query()->active();

        $this->assertEquals('select * from "brands" where "is_active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    /**
     * Test scopeSearch adds where like clause for name.
     */
    public function test_scope_search(): void
    {
        $query = Brand::query()->search('test');

        $this->assertEquals('select * from "brands" where "name" like ?', $query->toSql());
        $this->assertEquals(['%test%'], $query->getBindings());
    }

    /**
     * Test that slug is auto-generated on creating if empty.
     */
    public function test_slug_auto_generated_on_creating(): void
    {
        $brand = new Brand(['name' => 'Test Brand']);
        $brand->save();

        $this->assertEquals('test-brand', $brand->slug);
        $brand->delete(); // cleanup
    }

    /**
     * Test that slug is auto-generated on updating if name changed and slug empty.
     */
    public function test_slug_auto_generated_on_updating(): void
    {
        $brand = new Brand(['name' => 'Old Name', 'slug' => 'old-slug']);
        $brand->save();

        $brand->name = 'New Name';
        $brand->slug = null;
        $brand->save();

        $this->assertEquals('new-name', $brand->slug);
        $brand->delete();
    }

    /**
     * Test getRules returns the validation rules.
     */
    public function test_get_rules(): void
    {
        $brand = new Brand;
        $rules = $brand->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('is_active', $rules);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
