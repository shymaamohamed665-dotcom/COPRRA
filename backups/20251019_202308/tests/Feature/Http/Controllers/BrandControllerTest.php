<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Brand;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class BrandControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

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

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_brands_index()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/brands');

        $response->assertStatus(200)
            ->assertViewIs('brands.index');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_create_brand_form()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/brands/create');

        $response->assertStatus(200)
            ->assertViewIs('brands.create');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_store_new_brand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brandData = [
            'name' => 'Test Brand',
            'slug' => 'test-brand',
            'description' => 'Test Brand Description',
            'website_url' => 'https://testbrand.com',
            'logo_url' => 'https://testbrand.com/logo.png',
        ];

        $response = $this->post('/brands', $brandData);

        $response->assertStatus(302)
            ->assertRedirect('/brands');

        $this->assertDatabaseHas('brands', [
            'name' => 'Test Brand',
            'description' => 'Test Brand Description',
            'website_url' => 'https://testbrand.com',
            'logo_url' => 'https://testbrand.com/logo.png',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_brand_creation_request()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->post('/brands', []);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_brand_details()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->get('/brands/'.$brand->id);

        $response->assertStatus(200)
            ->assertViewIs('brands.show')
            ->assertViewHas('brand', $brand);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_for_nonexistent_brand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->get('/brands/999');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_edit_brand_form()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->get('/brands/'.$brand->id.'/edit');

        $response->assertStatus(200)
            ->assertViewIs('brands.edit')
            ->assertViewHas('brand', $brand);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_update_brand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $updateData = [
            'name' => 'Updated Brand Name',
            'slug' => 'updated-brand-name',
            'description' => 'Updated Brand Description',
            'website_url' => 'https://updatedbrand.com',
            'logo_url' => 'https://updatedbrand.com/logo.png',
        ];

        $response = $this->put('/brands/'.$brand->id, $updateData);

        $response->assertStatus(302)
            ->assertRedirect('/brands');

        $this->assertDatabaseHas('brands', [
            'id' => $brand->id,
            'name' => 'Updated Brand Name',
            'description' => 'Updated Brand Description',
            'website_url' => 'https://updatedbrand.com',
            'logo_url' => 'https://updatedbrand.com/logo.png',
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_validates_brand_update_request()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->put('/brands/'.$brand->id, [
            'name' => '', // Empty name should fail validation
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors(['name']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_delete_brand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        $response = $this->delete('/brands/'.$brand->id);

        $response->assertStatus(302)
            ->assertRedirect('/brands');

        $this->assertDatabaseMissing('brands', [
            'id' => $brand->id,
        ]);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_returns_404_when_deleting_nonexistent_brand()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $response = $this->delete('/brands/999');

        $response->assertStatus(404);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_requires_authentication_for_all_brand_routes()
    {
        $response = $this->get('/brands');
        $response->assertStatus(302); // Redirect to login

        $response = $this->get('/brands/create');
        $response->assertStatus(302);

        $response = $this->post('/brands', []);
        $response->assertStatus(302);

        $response = $this->get('/brands/1');
        $response->assertStatus(302);

        $response = $this->get('/brands/1/edit');
        $response->assertStatus(302);

        $response = $this->put('/brands/1', []);
        $response->assertStatus(302);

        $response = $this->delete('/brands/1');
        $response->assertStatus(302);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_brand_creation_errors_gracefully()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        // Test with invalid data that should cause validation error
        $response = $this->post('/brands', [
            'name' => '', // Empty name should fail validation
            'slug' => '', // Empty slug should fail validation
            'description' => 'Test Description',
        ]);

        $response->assertStatus(302); // Should redirect back with validation errors
        $response->assertSessionHasErrors(['name', 'slug']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_brand_update_errors_gracefully()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        // Test with invalid data that should cause validation error
        $response = $this->put('/brands/'.$brand->id, [
            'name' => '', // Empty name should fail validation
            'slug' => '', // Empty slug should fail validation
            'description' => 'Updated Description',
        ]);

        $response->assertStatus(302); // Should redirect back with validation errors
        $response->assertSessionHasErrors(['name', 'slug']);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_handles_brand_deletion_errors_gracefully()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand = Brand::factory()->create();

        // Mock a database error
        $this->mock(\App\Models\Brand::class, function ($mock) {
            $mock->shouldReceive('findOrFail')
                ->andThrow(new \Exception('Database error'));
        });

        $response = $this->delete('/brands/'.$brand->id);

        $response->assertStatus(500);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_display_brands_with_pagination()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        // Create multiple brands
        Brand::factory()->count(15)->create();

        $response = $this->get('/brands');

        $response->assertStatus(200)
            ->assertViewIs('brands.index');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_search_brands()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $brand1 = Brand::factory()->create(['name' => 'Apple']);
        $brand2 = Brand::factory()->create(['name' => 'Samsung']);

        $response = $this->get('/brands?search=Apple');

        $response->assertStatus(200)
            ->assertViewIs('brands.index');
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function it_can_sort_brands()
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        Brand::factory()->count(5)->create();

        $response = $this->get('/brands?sort=name&direction=asc');

        $response->assertStatus(200)
            ->assertViewIs('brands.index');
    }
}
