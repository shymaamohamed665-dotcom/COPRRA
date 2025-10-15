<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class WishlistControllerTest extends TestCase
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

    public function test_can_display_wishlist()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->get('/wishlist');

        $response->assertStatus(200)
            ->assertViewIs('wishlist.index');
    }

    public function test_requires_authentication_to_view_wishlist()
    {
        $response = $this->get('/wishlist');

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }

    public function test_can_add_product_to_wishlist()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();

        $response = $this->post('/wishlist/add', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product added to wishlist successfully.',
            ]);

        // Verify the item was added to the database
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_validates_add_to_wishlist_request()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->postJson('/wishlist/add', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_requires_authentication_to_add_to_wishlist()
    {
        $product = Product::factory()->create();

        $response = $this->post('/wishlist/add', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }

    public function test_can_remove_product_from_wishlist()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product = Product::factory()->create();

        // First add product to wishlist
        $this->post('/wishlist/add', [
            'product_id' => $product->id,
        ]);

        // Verify the item was added
        $this->assertDatabaseHas('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);

        $response = $this->delete('/wishlist/remove', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Product removed from wishlist successfully.',
            ]);

        // Verify the item was removed from the database
        $this->assertDatabaseMissing('wishlists', [
            'user_id' => $user->id,
            'product_id' => $product->id,
        ]);
    }

    public function test_validates_remove_from_wishlist_request()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->deleteJson('/wishlist/remove', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['product_id']);
    }

    public function test_requires_authentication_to_remove_from_wishlist()
    {
        $product = Product::factory()->create();

        $response = $this->delete('/wishlist/remove', [
            'product_id' => $product->id,
        ]);

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }

    public function test_can_clear_entire_wishlist()
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        // Add products to wishlist
        $this->post('/wishlist/add', [
            'product_id' => $product1->id,
        ]);
        $this->post('/wishlist/add', [
            'product_id' => $product2->id,
        ]);

        $response = $this->delete('/wishlist/clear');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Wishlist cleared successfully.',
            ]);
    }

    public function test_requires_authentication_to_clear_wishlist()
    {
        $response = $this->delete('/wishlist/clear');

        $response->assertStatus(302)
            ->assertRedirect('/login');
    }
}
