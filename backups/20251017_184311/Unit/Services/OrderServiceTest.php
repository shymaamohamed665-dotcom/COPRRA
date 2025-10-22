<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderServiceTest extends TestCase
{
    use RefreshDatabase;

    private OrderService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OrderService;
    }

    public function test_create_order_creates_order_with_items(): void
    {
        // Arrange
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 10.00]);
        $cartItems = [['product_id' => $product->id, 'quantity' => 2]];
        $addresses = ['shipping' => ['address' => '123 Main St'], 'billing' => ['address' => '123 Main St']];

        // Act
        $order = $this->service->createOrder($user, $cartItems, $addresses);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals($user->id, $order->user_id);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(20.00, $order->subtotal); // 2 * 10
        $this->assertEquals(1, $order->items->count());
        $this->assertEquals($product->id, $order->items->first()->product_id);
    }

    public function test_update_order_status_updates_valid_transition(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'pending']);

        // Act
        $result = $this->service->updateOrderStatus($order, 'processing');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('processing', $order->fresh()->status);
    }

    public function test_update_order_status_fails_invalid_transition(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'delivered']);

        // Act
        $result = $this->service->updateOrderStatus($order, 'processing');

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('delivered', $order->fresh()->status);
    }

    public function test_cancel_order_cancels_pending_order(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'pending']);
        $product = Product::factory()->create(['stock' => 10]);
        OrderItem::factory()->create(['order_id' => $order->id, 'product_id' => $product->id, 'quantity' => 2]);

        // Act
        $result = $this->service->cancelOrder($order, 'Changed mind');

        // Assert
        $this->assertTrue($result);
        $this->assertEquals('cancelled', $order->fresh()->status);
        $this->assertEquals(12, $product->fresh()->stock); // 10 + 2
    }

    public function test_cancel_order_fails_for_shipped_order(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'shipped']);

        // Act
        $result = $this->service->cancelOrder($order, 'Changed mind');

        // Assert
        $this->assertFalse($result);
        $this->assertEquals('shipped', $order->fresh()->status);
    }

    public function test_get_order_history_returns_user_orders(): void
    {
        // Arrange
        $user = User::factory()->create();
        Order::factory()->create(['user_id' => $user->id]);
        Order::factory()->create(); // Other user

        // Act
        $orders = $this->service->getOrderHistory($user, 10);

        // Assert
        $this->assertCount(1, $orders);
        $this->assertEquals($user->id, $orders->first()->user_id);
    }
}
