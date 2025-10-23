<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Events\OrderStatusChanged;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class OrderServiceCoverageTest extends TestCase
{
    public function test_create_order_computes_totals_and_persists_items(): void
    {
        $user = User::factory()->create();
        $p1 = Product::factory()->create(['price' => 40.00]);
        $p2 = Product::factory()->create(['price' => 25.50]);

        $cartItems = [
            ['product_id' => $p1->id, 'quantity' => 2],
            ['product_id' => $p2->id, 'quantity' => 1],
        ];

        $addresses = [
            'shipping' => '123 Test St, City',
            'billing' => '456 Billing Ave, City',
        ];

        $service = new OrderService;
        $order = $service->createOrder($user, $cartItems, $addresses);

        $this->assertInstanceOf(Order::class, $order);
        $this->assertEquals(OrderStatus::PENDING, $order->status_enum);

        // Subtotal = (40 * 2) + (25.5 * 1) = 105.5
        // Tax (10%) = 10.55, Shipping (<= 100 threshold -> 10) => subtotal (105.5) > 100 so free
        $expectedSubtotal = 105.50;
        $expectedTax = round($expectedSubtotal * 0.10, 2);
        $expectedShipping = 0.0; // free shipping threshold default is 100
        $expectedTotal = round($expectedSubtotal + $expectedTax + $expectedShipping, 2);

        $this->assertSame(round($expectedSubtotal, 2), (float) $order->subtotal);
        $this->assertSame(round($expectedTax, 2), (float) $order->tax_amount);
        $this->assertSame($expectedShipping, (float) $order->shipping_amount);
        $this->assertSame($expectedTotal, (float) $order->total_amount);

        $this->assertCount(2, $order->items);

        // Each item total should be computed by model booted hooks (unit_price * quantity)
        $item1 = $order->items->firstWhere('product_id', $p1->id);
        $item2 = $order->items->firstWhere('product_id', $p2->id);
        $this->assertNotNull($item1);
        $this->assertNotNull($item2);
        $this->assertSame(2, $item1->quantity);
        $this->assertSame(1, $item2->quantity);
        $this->assertSame(40.00, (float) $item1->unit_price);
        $this->assertSame(25.50, (float) $item2->unit_price);
        $this->assertSame(80.00, (float) $item1->total_price);
        $this->assertSame(25.50, (float) $item2->total_price);
    }

    public function test_update_order_status_allows_transition_and_fires_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 20.0]);
        $order = (new OrderService)->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        $service = new OrderService;
        // PENDING -> PROCESSING (allowed)
        $this->assertTrue($service->updateOrderStatus($order, OrderStatus::PROCESSING));
        $this->assertEquals(OrderStatus::PROCESSING, $order->status_enum);
        Event::assertDispatched(OrderStatusChanged::class);

        // PROCESSING -> SHIPPED (allowed)
        $this->assertTrue($service->updateOrderStatus($order, OrderStatus::SHIPPED));
        $this->assertEquals(OrderStatus::SHIPPED, $order->status_enum);
        $this->assertNotNull($order->shipped_at);
        Event::assertDispatched(OrderStatusChanged::class);
    }

    public function test_cancel_order_restores_stock_and_returns_true_for_pending(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 15.0, 'stock_quantity' => 5]);

        $order = (new OrderService)->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 3],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        $service = new OrderService;
        $this->assertTrue($service->cancelOrder($order, 'Customer request'));

        $product->refresh();
        // Stock restored by incrementing stock_quantity preferred over legacy stock
        $this->assertSame(8, (int) $product->stock_quantity);
        $this->assertEquals(OrderStatus::CANCELLED, $order->status_enum);
    }

    public function test_update_order_status_rejects_invalid_transition(): void
    {
        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 50.0]);
        $order = (new OrderService)->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        // Force final state to DELIVERED
        $order->update(['status' => OrderStatus::DELIVERED, 'delivered_at' => now()]);

        $service = new OrderService;
        // DELIVERED -> PROCESSING should be rejected
        $this->assertFalse($service->updateOrderStatus($order, OrderStatus::PROCESSING));
        $this->assertEquals(OrderStatus::DELIVERED, $order->status_enum);
    }

    public function test_create_order_respects_shipping_threshold_boundary_and_tax_config(): void
    {
        Config::set('coprra.tax.rate', 0.10);
        Config::set('coprra.shipping.free_threshold', 100);
        Config::set('coprra.shipping.standard_fee', 10);

        $user = User::factory()->create();
        $p1 = Product::factory()->create(['price' => 50.00]);
        $p2 = Product::factory()->create(['price' => 50.00]);

        // Subtotal equals free shipping threshold (100) -> shipping should apply (10)
        $cartItems = [
            ['product_id' => $p1->id, 'quantity' => 1],
            ['product_id' => $p2->id, 'quantity' => 1],
        ];

        $addresses = [
            'shipping' => '123 Test St, City',
            'billing' => '456 Billing Ave, City',
        ];

        $order = (new OrderService)->createOrder($user, $cartItems, $addresses);

        $expectedSubtotal = 100.00;
        $expectedTax = round($expectedSubtotal * 0.10, 2); // 10.00
        $expectedShipping = 10.00; // boundary equals threshold -> fee applies
        $expectedTotal = round($expectedSubtotal + $expectedTax + $expectedShipping, 2); // 120.00

        $this->assertSame($expectedSubtotal, (float) $order->subtotal);
        $this->assertSame($expectedTax, (float) $order->tax_amount);
        $this->assertSame($expectedShipping, (float) $order->shipping_amount);
        $this->assertSame($expectedTotal, (float) $order->total_amount);
    }

    public function test_update_order_status_accepts_completed_alias_as_delivered_and_fires_event(): void
    {
        Event::fake();

        $user = User::factory()->create();
        $product = Product::factory()->create(['price' => 30.0]);
        $order = (new OrderService)->createOrder($user, [
            ['product_id' => $product->id, 'quantity' => 1],
        ], [
            'shipping' => 'Shipping Address',
            'billing' => 'Billing Address',
        ]);

        $service = new OrderService;

        // Move through allowed transitions before aliasing to delivered
        $this->assertTrue($service->updateOrderStatus($order, OrderStatus::PROCESSING));
        $this->assertTrue($service->updateOrderStatus($order, OrderStatus::SHIPPED));

        // Alias 'completed' should map to DELIVERED and be allowed from SHIPPED
        $this->assertTrue($service->updateOrderStatus($order, 'completed'));
        $this->assertEquals(OrderStatus::DELIVERED, $order->status_enum);
        Event::assertDispatched(OrderStatusChanged::class);
        Event::assertDispatched(OrderStatusChanged::class, function (OrderStatusChanged $event) use ($order) {
            return $event->order->is($order)
                && $event->oldStatus === OrderStatus::SHIPPED
                && $event->newStatus === OrderStatus::DELIVERED;
        });
    }
}
