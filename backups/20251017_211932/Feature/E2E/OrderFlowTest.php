<?php

declare(strict_types=1);

namespace Tests\Feature\E2E;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderFlowTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->product = Product::factory()->create([
            'price' => 100.00,
            'quantity' => 10,
            'is_active' => true,
        ]);
    }

    public function test_complete_order_creation_flow(): void
    {
        $this->actingAs($this->user);

        // Step 1: Add product to cart
        $response = $this->post('/cart', [
            'product_id' => $this->product->id,
            'quantity' => 2,
        ]);
        $response->assertStatus(201);

        // Step 2: View cart
        $response = $this->get('/cart');
        $response->assertStatus(200);

        // Step 3: Proceed to checkout
        $response = $this->get('/checkout');
        $response->assertStatus(200);

        // Step 4: Submit order
        $response = $this->post('/orders', [
            'shipping_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'USA',
            ],
            'billing_address' => [
                'street' => '123 Main St',
                'city' => 'New York',
                'state' => 'NY',
                'zip' => '10001',
                'country' => 'USA',
            ],
        ]);

        $response->assertRedirect();

        // Step 5: Verify order was created
        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING->value,
        ]);

        // Step 6: Verify cart was cleared
        $this->assertDatabaseMissing('cart_items', [
            'user_id' => $this->user->id,
        ]);
    }

    public function test_complete_order_status_update_flow(): void
    {
        $this->actingAs($this->user);

        // Step 1: Create order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
        ]);

        // Step 2: View order
        $response = $this->get("/orders/{$order->id}");
        $response->assertStatus(200);

        // Step 3: Admin updates status to processing
        $adminUser = User::factory()->create(['role' => 'admin']);
        $this->actingAs($adminUser);

        $response = $this->patch("/orders/{$order->id}/status", [
            'status' => 'processing',
        ]);
        $response->assertStatus(200);

        // Step 4: Verify status updated
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::PROCESSING->value,
        ]);

        // Step 5: Verify notification was created
        $this->assertDatabaseHas('notifications', [
            'user_id' => $this->user->id,
            'type' => 'order_status',
        ]);
    }

    public function test_order_cancellation_flow(): void
    {
        $this->actingAs($this->user);

        // Step 1: Create order
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
        ]);

        // Step 2: Cancel order
        $response = $this->post("/orders/{$order->id}/cancel", [
            'reason' => 'Changed my mind',
        ]);

        $response->assertStatus(200);

        // Step 3: Verify order was cancelled
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::CANCELLED->value,
        ]);
    }

    public function test_cannot_cancel_shipped_order(): void
    {
        $this->actingAs($this->user);

        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::SHIPPED,
        ]);

        $response = $this->post("/orders/{$order->id}/cancel");

        $response->assertStatus(422);

        // Verify order status unchanged
        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'status' => OrderStatus::SHIPPED->value,
        ]);
    }

    public function test_user_can_view_order_history(): void
    {
        $this->actingAs($this->user);

        // Create multiple orders
        Order::factory()->count(5)->create(['user_id' => $this->user->id]);
        Order::factory()->count(3)->create(); // Other user's orders

        $response = $this->get('/orders');

        $response->assertStatus(200);
        // Should only see own orders
        $response->assertSee($this->user->name);
    }

    public function test_user_cannot_view_other_users_orders(): void
    {
        $this->actingAs($this->user);

        $otherOrder = Order::factory()->create();

        $response = $this->get("/orders/{$otherOrder->id}");

        $response->assertStatus(403);
    }
}
