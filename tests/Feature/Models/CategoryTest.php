<?php

namespace Tests\Feature\Models;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class CategoryTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_a_category(): void
    {
        // Arrange
        $attributes = [
            'name' => 'Test Category',
            'slug' => 'test-category',
            'description' => 'A test category',
            'level' => 0,
            'is_active' => true,
        ];

        // Act
        $category = Category::create($attributes);

        // Assert
        $this->assertInstanceOf(Category::class, $category);
        $this->assertEquals('Test Category', $category->name);
        $this->assertEquals('test-category', $category->slug);
        $this->assertEquals(0, $category->level);
        $this->assertTrue($category->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_has_products_relationship(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory()->create(['category_id' => $category->id]);

        // Act
        $category->refresh();

        // Assert
        $this->assertCount(1, $category->products);
        $this->assertInstanceOf(Product::class, $category->products->first());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_validate_required_fields(): void
    {
        // Arrange
        $category = new Category;

        // Act
        $rules = $category->getRules();

        // Assert
        $this->assertArrayHasKey('name', $rules);
        $this->assertEquals('required|string|max:255', $rules['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_validate_name_length(): void
    {
        // Arrange & Act
        $category = Category::factory()->make(['name' => str_repeat('a', 256)]);

        // Assert
        $this->assertFalse($category->validate());
        $this->assertArrayHasKey('name', $category->getErrors());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_scope_active_categories(): void
    {
        // Arrange
        Category::factory()->create(['is_active' => true]);
        Category::factory()->create(['is_active' => false]);

        // Act
        $activeCategories = Category::active()->get();

        // Assert
        $this->assertCount(1, $activeCategories);
        $this->assertTrue($activeCategories->first()->is_active);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_search_categories_by_name(): void
    {
        // Arrange
        Category::factory()->create(['name' => 'Electronics']);
        Category::factory()->create(['name' => 'Clothing']);

        // Act
        $results = Category::search('Electro')->get();

        // Assert
        $this->assertCount(1, $results);
        $this->assertEquals('Electronics', $results->first()->name);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_get_category_with_products_count(): void
    {
        // Arrange
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id]);

        // Act
        $categoryWithCount = Category::withCount('products')->find($category->id);

        // Assert
        $this->assertEquals(2, $categoryWithCount->products_count);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_soft_delete_category(): void
    {
        // Arrange
        $category = Category::factory()->create();

        // Act
        $category->delete();

        // Assert
        $this->assertSoftDeleted($category);
        $this->assertNull(Category::find($category->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_restore_soft_deleted_category(): void
    {
        // Arrange
        $category = Category::factory()->create();
        $category->delete();

        // Act
        $category->restore();

        // Assert
        $this->assertNotSoftDeleted($category);
        $this->assertNotNull(Category::find($category->id));
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_auto_generates_slug_from_name(): void
    {
        // Arrange & Act
        $category = Category::create(['name' => 'Test Category Name']);

        // Assert
        $this->assertEquals('test-category-name', $category->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_updates_slug_when_name_changes(): void
    {
        // Arrange
        $category = Category::factory()->create(['name' => 'Old Name']);

        // Act
        $category->update(['name' => 'New Name']);

        // Assert
        $this->assertEquals('new-name', $category->slug);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_category_hierarchy_parent_child(): void
    {
        // Arrange
        $parent = Category::factory()->create(['level' => 0]);
        $child = Category::factory()->create(['parent_id' => $parent->id]);

        // Act
        $child->refresh();

        // Assert
        $this->assertEquals(1, $child->level);
        $this->assertEquals($parent->id, $child->parent_id);
        $this->assertInstanceOf(Category::class, $child->parent);
        $this->assertEquals($parent->id, $child->parent->id);
        $this->assertCount(1, $parent->children);
        $this->assertEquals($child->id, $parent->children->first()->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_level_calculation_on_parent_change(): void
    {
        // Arrange
        $parent = Category::factory()->create(['level' => 0]);
        $child = Category::factory()->create(['level' => 1, 'parent_id' => $parent->id]);
        $grandchild = Category::factory()->create(['level' => 2, 'parent_id' => $child->id]);

        // Act
        $grandchild->update(['parent_id' => $parent->id]);

        // Assert
        $this->assertEquals(1, $grandchild->level);
    }
}
