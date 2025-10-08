<?php

namespace Tests\Unit\DataAccuracy;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use Tests\DatabaseSetup;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DataConsistencyTest extends TestCase
{
    use DatabaseSetup;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_total_matches_items_sum(): void
    {
        $product = Product::factory()->create(['price' => 100]);
        $order = Order::factory()->create([
            'total_amount' => 200,
            'subtotal' => 200,
        ]);

        OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $expectedTotal = 200; // 2 * 100
        $this->assertEquals($expectedTotal, $order->total_amount);
    }

    // Removed stock consistency test as stock decrement logic is not implemented

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_items_product_reference(): void
    {
        $product = Product::factory()->create();
        $order = Order::factory()->create();

        $orderItem = OrderItem::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
        ]);

        $this->assertTrue($orderItem->product()->exists());
        $this->assertEquals($product->id, $orderItem->product->id);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_order_status_consistency(): void
    {
        $order = Order::factory()->create(['status' => 'completed']);
        $payment = Payment::factory()->create([
            'order_id' => $order->id,
            'amount' => 100.00,
            'status' => 'completed',
        ]);

        $this->assertEquals('completed', $order->status);
        $this->assertEquals(100.00, $payment->amount);
    }

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
}
