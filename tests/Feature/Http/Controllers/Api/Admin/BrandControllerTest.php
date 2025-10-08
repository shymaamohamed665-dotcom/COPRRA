<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api\Admin;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BrandControllerTest extends TestCase
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
    public function test_can_list_brands()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $brands = Brand::factory()->count(3)->create();

        $response = $this->getJson('/api/admin/brands');

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
    public function test_requires_admin_authentication_to_list_brands()
    {
        $response = $this->getJson('/api/admin/brands');

        $response->assertStatus(401);
    }
    public function test_requires_admin_role_to_list_brands()
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $response = $this->getJson('/api/admin/brands');

        $response->assertStatus(403);
    }
    public function test_can_show_specific_brand()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $brand = Brand::factory()->create();

        $response = $this->getJson("/api/admin/brands/{$brand->id}");

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $brand->id,
                    'name' => $brand->name,
                    'slug' => $brand->slug,
                    'description' => $brand->description,
                ],
            ]);
    }
    public function test_returns_404_for_nonexistent_brand()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->getJson('/api/admin/brands/999');

        $response->assertStatus(404);
    }
    public function test_can_create_brand()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $brandData = [
            'name' => 'Test Brand',
            'description' => 'Test Brand Description',
        ];

        $response = $this->postJson('/api/admin/brands', $brandData);

        $response->assertStatus(201)
            ->assertJson([
                'data' => [
                    'name' => $brandData['name'],
                    'description' => $brandData['description'],
                ],
            ]);

        // Assert that the brand was actually saved to the database
        $this->assertDatabaseHas('brands', [
            'name' => $brandData['name'],
            'description' => $brandData['description'],
        ]);
    }
    public function test_validates_brand_creation_request()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->postJson('/api/admin/brands', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
    public function test_requires_admin_authentication_to_create_brand()
    {
        $brandData = [
            'name' => 'Test Brand',
            'description' => 'Test Brand Description',
        ];

        $response = $this->postJson('/api/admin/brands', $brandData);

        $response->assertStatus(401);
    }
    public function test_can_update_brand()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $brand = Brand::factory()->create();

        $updateData = [
            'name' => 'Updated Brand Name',
            'description' => 'Updated Brand Description',
        ];

        $response = $this->putJson("/api/admin/brands/{$brand->id}", $updateData);

        $response->assertStatus(200)
            ->assertJson([
                'data' => [
                    'id' => $brand->id,
                    'name' => $updateData['name'],
                    'description' => $updateData['description'],
                ],
            ]);

        // Assert that the brand was actually updated in the database
        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => $updateData['name'],
            'description' => $updateData['description'],
        ]);
    }
    public function test_validates_brand_update_request()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $brand = Brand::factory()->create();

        $response = $this->putJson("/api/admin/brands/{$brand->id}", []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name']);
    }
    public function test_requires_admin_authentication_to_update_brand()
    {
        $brand = Brand::factory()->create();

        $updateData = [
            'name' => 'Updated Brand Name',
            'description' => 'Updated Brand Description',
        ];

        $response = $this->putJson("/api/admin/brands/{$brand->id}", $updateData);

        $response->assertStatus(401);
    }
    public function test_can_delete_brand()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $brand = Brand::factory()->create();

        $response = $this->deleteJson("/api/admin/brands/{$brand->id}");

        $response->assertStatus(204);

        // Assert that the brand was actually deleted from the database
        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }
    public function test_returns_404_when_deleting_nonexistent_brand()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $this->actingAs($admin);

        $response = $this->deleteJson('/api/admin/brands/999');

        $response->assertStatus(404);
    }
    public function test_requires_admin_authentication_to_delete_brand()
    {
        $brand = Brand::factory()->create();

        $response = $this->deleteJson("/api/admin/brands/{$brand->id}");

        $response->assertStatus(401);
    }
}
