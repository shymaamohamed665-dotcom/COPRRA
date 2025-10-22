<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class CategoryControllerTest extends TestCase
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

    public function test_can_list_categories()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $categories = Category::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'name',
                        'slug',
                        'description',
                        'created_at',
                        'updated_at',
                    ],
                ],
            ]);
    }

    public function test_requires_admin_authentication_to_list_categories()
    {
        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(401);
    }

    public function test_requires_admin_role_to_list_categories()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $response = $this->getJson('/api/admin/categories');

        $response->assertStatus(403);
    }

    public function test_can_show_specific_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->getJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                    'description' => $category->description,
                ],
            ]);
    }

    public function test_returns_404_for_nonexistent_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/categories/999');

        $response->assertStatus(404);
    }

    public function test_can_create_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ];

        $response = $this->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => $categoryData['name'],
                    'description' => $categoryData['description'],
                ],
            ]);

        // Assert that the category was actually saved to the database
        $this->assertDatabaseHas('categories', [
            'name' => $categoryData['name'],
            'description' => $categoryData['description'],
        ]);
    }

    public function test_validates_category_creation_request()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->postJson('/api/admin/categories', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_requires_admin_authentication_to_create_category()
    {
        $categoryData = [
            'name' => 'Test Category',
            'description' => 'Test Category Description',
        ];

        $response = $this->postJson('/api/admin/categories', $categoryData);

        $response->assertStatus(401);
    }

    public function test_can_update_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Category Description',
        ];

        $response = $this->putJson("/api/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $category->id,
                    'name' => $updateData['name'],
                    'description' => $updateData['description'],
                ],
            ]);
    }

    public function test_validates_category_update_request()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->putJson("/api/admin/categories/{$category->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }

    public function test_requires_admin_authentication_to_update_category()
    {
        $category = Category::factory()->create();

        $updateData = [
            'name' => 'Updated Category Name',
            'description' => 'Updated Category Description',
        ];

        $response = $this->putJson("/api/admin/categories/{$category->id}", $updateData);

        $response->assertStatus(401);
    }

    public function test_can_delete_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(204);
    }

    public function test_returns_404_when_deleting_nonexistent_category()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->deleteJson('/api/admin/categories/999');

        $response->assertStatus(404);
    }

    public function test_requires_admin_authentication_to_delete_category()
    {
        $category = Category::factory()->create();

        $response = $this->deleteJson("/api/admin/categories/{$category->id}");

        $response->assertStatus(401);
    }
}
