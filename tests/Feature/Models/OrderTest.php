<?php

declare(strict_types=1);

namespace Tests\Feature\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class OrderTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_it_can_create_an_order(): void
    {
        // Arrange
        $user = User::factory()->create();
        $attributes = [
            'order_number' => 'ORD-001',
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => 100.00,
            'subtotal' => 90.00,
            'tax_amount' => 5.00,
            'shipping_amount' => 5.00,
            'discount_amount' => 0.00,
            'currency' => 'USD',
            'shipping_address' => ['street' => '123 Main St'],
            'billing_address' => ['street' => '123 Main St'],
            'notes' => 'Test order',
        ];

        // Act
        $order = Order::create($attributes);

        // Assert
        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals('ORD-001', $order->order_number);
        $this->assertEquals('pending', $order->status);
        $this->assertEquals(100.00, $order->total_amount);
        $this->assertEquals('USD', $order->currency);
        $this->assertIsArray($order->shipping_address);
        $this->assertIsArray($order->billing_address);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_relationships(): void
    {
        // Arrange
        $user = User::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id]);
        OrderItem::factory()->create(['order_id' => $order->id]);
        Payment::factory()->create(['order_id' => $order->id]);

        // Act
        $order->refresh();

        // Assert
        $this->assertInstanceOf(User::class, $order->user);
        $this->assertEquals($user->id, $order->user->id);
        $this->assertCount(1, $order->items);
        $this->assertCount(1, $order->payments);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_casts_attributes_correctly(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'shipping_address' => ['street' => '123 Main St', 'city' => 'Test City'],
            'billing_address' => ['street' => '456 Billing St'],
            'shipped_at' => '2023-01-01 10:00:00',
            'delivered_at' => '2023-01-02 15:00:00',
        ]);

        // Act & Assert
        $this->assertIsArray($order->shipping_address);
        $this->assertEquals('123 Main St', $order->shipping_address['street']);
        $this->assertIsArray($order->billing_address);
        $this->assertInstanceOf(\Carbon\Carbon::class, $order->shipped_at);
        $this->assertInstanceOf(\Carbon\Carbon::class, $order->delivered_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_by_status(): void
    {
        // Arrange
        Order::factory()->create(['status' => 'pending']);
        Order::factory()->create(['status' => 'completed']);
        Order::factory()->create(['status' => 'pending']);

        // Act
        $pendingOrders = Order::byStatus('pending')->get();

        // Assert
        $this->assertCount(2, $pendingOrders);
        $pendingOrders->each(function ($order) {
            $this->assertEquals('pending', $order->status);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_scope_for_user(): void
    {
        // Arrange
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();
        Order::factory()->create(['user_id' => $user1->id]);
        Order::factory()->create(['user_id' => $user2->id]);
        Order::factory()->create(['user_id' => $user1->id]);

        // Act
        $userOrders = Order::forUser($user1->id)->get();

        // Assert
        $this->assertCount(2, $userOrders);
        $userOrders->each(function ($order) use ($user1) {
            $this->assertEquals($user1->id, $order->user_id);
        });
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_fillable_attributes(): void
    {
        // Arrange
        $fillable = [
            'order_number',
            'user_id',
            'status',
            'total_amount',
            'subtotal',
            'tax_amount',
            'shipping_amount',
            'discount_amount',
            'currency',
            'shipping_address',
            'billing_address',
            'notes',
            'order_date',
            'shipped_at',
            'delivered_at',
        ];

        // Act
        $order = new Order;

        // Assert
        $this->assertEquals($fillable, $order->getFillable());
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_status_transitions(): void
    {
        // Arrange
        $order = Order::factory()->create(['status' => 'pending']);

        // Act
        $order->update(['status' => 'shipped', 'shipped_at' => now()]);
        $order->update(['status' => 'delivered', 'delivered_at' => now()]);

        // Assert
        $this->assertEquals('delivered', $order->status);
        $this->assertNotNull($order->shipped_at);
        $this->assertNotNull($order->delivered_at);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_totals_calculation(): void
    {
        // Arrange
        $order = Order::factory()->create([
            'subtotal' => 100.00,
            'tax_amount' => 10.00,
            'shipping_amount' => 5.00,
            'discount_amount' => 5.00,
        ]);

        // Act & Assert
        $this->assertEquals(100.00, $order->subtotal);
        $this->assertEquals(10.00, $order->tax_amount);
        $this->assertEquals(5.00, $order->shipping_amount);
        $this->assertEquals(5.00, $order->discount_amount);
        $this->assertEquals(110.00, $order->total_amount); // subtotal + tax + shipping - discount
    }
}
