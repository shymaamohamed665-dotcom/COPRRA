<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
    }

    public function test_user_can_list_their_orders(): void
    {
        Order::factory()->count(3)->create(['user_id' => $this->user->id]);
        Order::factory()->count(2)->create(); // Other user's orders

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data.data');
    }

    public function test_user_can_filter_orders_by_status(): void
    {
        Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING,
        ]);
        Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PROCESSING,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders?status=pending');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data.data')
            ->assertJsonPath('data.data.0.status.value', 'pending');
    }

    public function test_user_can_view_single_order(): void
    {
        $order = Order::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order->id)
            ->assertJsonPath('data.order_number', $order->order_number);
    }

    public function test_user_cannot_view_other_users_order(): void
    {
        $otherOrder = Order::factory()->create();

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$otherOrder->id}");

        $response->assertStatus(403);
    }

    public function test_user_can_create_order(): void
    {
        $product = Product::factory()->create(['price' => 100.00, 'stock_quantity' => 10]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => $product->id, 'quantity' => 2],
                ],
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

        $response->assertStatus(201)
            ->assertJsonPath('data.status.value', 'pending')
            ->assertJsonPath('data.user_id', $this->user->id);

        $this->assertDatabaseHas('orders', [
            'user_id' => $this->user->id,
            'status' => OrderStatus::PENDING->value,
        ]);
    }

    public function test_create_order_validates_required_fields(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items', 'shipping_address', 'billing_address']);
    }

    public function test_create_order_validates_product_exists(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/orders', [
                'items' => [
                    ['product_id' => 99999, 'quantity' => 1],
                ],
                'shipping_address' => ['street' => '123 Main St'],
                'billing_address' => ['street' => '123 Main St'],
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['items.0.product_id']);
    }

    public function test_guest_cannot_access_orders(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    public function test_order_response_includes_status_details(): void
    {
        $order = Order::factory()->create([
            'user_id' => $this->user->id,
            'status' => OrderStatus::PROCESSING,
        ]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/orders/{$order->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'id',
                    'order_number',
                    'status' => ['value', 'label', 'color'],
                    'total_amount',
                    'created_at',
                ],
            ]);
    }

    public function test_order_list_is_paginated(): void
    {
        Order::factory()->count(20)->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/orders?per_page=10');

        $response->assertStatus(200)
            ->assertJsonCount(10, 'data.data')
            ->assertJsonStructure(['data', 'meta']);
    }
}
