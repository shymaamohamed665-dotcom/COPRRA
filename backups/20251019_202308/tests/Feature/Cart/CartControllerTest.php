<?php

declare(strict_types=1);

namespace Tests\Feature\Cart;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CartControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Clear cart before each test
        app('cart')->clear();
    }

    public function test_user_can_view_cart(): void
    {
        $response = $this->get('/cart');

        $response->assertStatus(200);
        $response->assertViewIs('cart.index');
        $response->assertViewHas(['cartItems', 'total']);
    }

    public function test_user_can_add_product_to_cart(): void
    {
        $product = Product::factory()->create([
            'name' => 'Test Product',
            'price' => 99.99,
            'stock_quantity' => 10,
        ]);

        $response = $this->post("/cart/add/{$product->id}", [
            'quantity' => 2,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $cartItems = app('cart')->getContent();
        $this->assertCount(1, $cartItems);
        $this->assertEquals(2, $cartItems->first()->quantity);
    }

    public function test_user_can_update_cart_quantity(): void
    {
        $product = Product::factory()->create(['price' => 50.00]);

        // Add product to cart
        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->post('/cart/update', [
            'id' => $cartItem->id,
            'quantity' => 5,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $updatedItem = app('cart')->get($cartItem->id);
        $this->assertEquals(5, $updatedItem->quantity);
    }

    public function test_user_cannot_update_cart_with_invalid_quantity(): void
    {
        $product = Product::factory()->create();

        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->post('/cart/update', [
            'id' => $cartItem->id,
            'quantity' => 0, // Invalid
        ]);

        $response->assertSessionHasErrors('quantity');
    }

    public function test_user_can_remove_item_from_cart(): void
    {
        $product = Product::factory()->create();

        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->delete("/cart/remove/{$cartItem->id}");

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertCount(0, app('cart')->getContent());
    }

    public function test_user_can_clear_entire_cart(): void
    {
        $products = Product::factory()->count(3)->create();

        foreach ($products as $product) {
            app('cart')->add([
                'id' => $product->id,
                'name' => $product->name,
                'price' => $product->price,
                'quantity' => 1,
                'attributes' => [],
            ]);
        }

        $this->assertCount(3, app('cart')->getContent());

        $response = $this->post('/cart/clear');

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertCount(0, app('cart')->getContent());
    }

    public function test_cart_calculates_total_correctly(): void
    {
        $product1 = Product::factory()->create(['price' => 10.00]);
        $product2 = Product::factory()->create(['price' => 20.00]);

        app('cart')->add([
            'id' => $product1->id,
            'name' => $product1->name,
            'price' => $product1->price,
            'quantity' => 2, // 2 * 10 = 20
            'attributes' => [],
        ]);

        app('cart')->add([
            'id' => $product2->id,
            'name' => $product2->name,
            'price' => $product2->price,
            'quantity' => 3, // 3 * 20 = 60
            'attributes' => [],
        ]);

        $total = app('cart')->getTotal();
        $this->assertEquals(80.00, $total); // 20 + 60 = 80
    }

    public function test_cart_persists_product_attributes(): void
    {
        $product = Product::factory()->create([
            'slug' => 'test-product',
            'image' => 'test-image.jpg',
        ]);

        $response = $this->post("/cart/add/{$product->id}", [
            'quantity' => 1,
            'attributes' => [
                'color' => 'red',
                'size' => 'large',
            ],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $this->assertEquals('test-product', $cartItem->attributes['slug']);
        $this->assertEquals('test-image.jpg', $cartItem->attributes['image']);
        $this->assertEquals('red', $cartItem->attributes['color']);
        $this->assertEquals('large', $cartItem->attributes['size']);
    }

    public function test_update_cart_request_validates_input(): void
    {
        $response = $this->post('/cart/update', [
            // Missing required fields
        ]);

        $response->assertSessionHasErrors(['id', 'quantity']);
    }

    public function test_quantity_cannot_exceed_maximum(): void
    {
        $product = Product::factory()->create();

        app('cart')->add([
            'id' => $product->id,
            'name' => $product->name,
            'price' => $product->price,
            'quantity' => 1,
            'attributes' => [],
        ]);

        $cartItem = app('cart')->getContent()->first();

        $response = $this->post('/cart/update', [
            'id' => $cartItem->id,
            'quantity' => 1000, // Exceeds max of 999
        ]);

        $response->assertSessionHasErrors('quantity');
    }
}
