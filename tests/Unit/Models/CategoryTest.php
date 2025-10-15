<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Testing\WithFaker;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for the Category model.
 *
 * @covers \App\Models\Category
 */
class CategoryTest extends TestCase
{
    use WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        $this->faker = $this->faker();
    }

    /**
     * Test that parent relation is a BelongsTo instance.
     */
    public function test_parent_relation(): void
    {
        $category = new Category;

        $relation = $category->parent();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Category::class, $relation->getRelated()::class);
    }

    /**
     * Test that children relation is a HasMany instance.
     */
    public function test_children_relation(): void
    {
        $category = new Category;

        $relation = $category->children();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Category::class, $relation->getRelated()::class);
    }

    /**
     * Test that products relation is a HasMany instance.
     */
    public function test_products_relation(): void
    {
        $category = new Category;

        $relation = $category->products();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive adds where clause for is_active.
     */
    public function test_scope_active(): void
    {
        $query = Category::query()->active();

        $this->assertEquals('select * from "categories" where "is_active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    /**
     * Test scopeSearch adds where like clause for name.
     */
    public function test_scope_search(): void
    {
        $query = Category::query()->search('test');

        $this->assertEquals('select * from "categories" where "name" like ?', $query->toSql());
        $this->assertEquals(['%test%'], $query->getBindings());
    }

    /**
     * Test that slug and level are auto-generated on creating.
     */
    public function test_slug_and_level_auto_generated_on_creating(): void
    {
        $category = new Category(['name' => 'Test Category']);
        $category->save();

        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals(0, $category->level);
        $category->delete();
    }

    /**
     * Test that level is set based on parent on creating.
     */
    public function test_level_based_on_parent_on_creating(): void
    {
        $parent = new Category(['name' => 'Parent', 'level' => 1]);
        $parent->save();

        $child = new Category(['name' => 'Child', 'parent_id' => $parent->id]);
        $child->save();

        $this->assertEquals(2, $child->level);
        $child->delete();
        $parent->delete();
    }

    /**
     * Test that slug is updated on name change.
     */
    public function test_slug_updated_on_name_change(): void
    {
        $category = new Category(['name' => 'Old Name']);
        $category->save();

        $category->name = 'New Name';
        $category->save();

        $this->assertEquals('new-name', $category->slug);
        $category->delete();
    }

    /**
     * Test that level is updated on parent_id change.
     */
    public function test_level_updated_on_parent_change(): void
    {
        $category = new Category(['name' => 'Category']);
        $category->save();

        $parent = new Category(['name' => 'Parent', 'level' => 1]);
        $parent->save();

        $category->parent_id = $parent->id;
        $category->save();

        $this->assertEquals(2, $category->level);
        $category->delete();
        $parent->delete();
    }

    /**
     * Test getRules returns the validation rules.
     */
    public function test_get_rules(): void
    {
        $category = new Category;
        $rules = $category->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('slug', $rules);
        $this->assertArrayHasKey('level', $rules);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
