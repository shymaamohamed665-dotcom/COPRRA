<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the OrderItem model.
 */
#[CoversClass(OrderItem::class)]
class OrderItemTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'order_id',
            'product_id',
            'quantity',
            'unit_price',
            'total_price',
            'product_details',
        ];

        $this->assertEquals($fillable, (new OrderItem)->getFillable());
    }

    /**
     * Test casts.
     */
    public function test_casts(): void
    {
        $casts = [
            'product_details' => 'array',
        ];

        $this->assertEquals($casts, (new OrderItem)->getCasts());
    }

    /**
     * Test order relation is a BelongsTo instance.
     */
    public function test_order_relation(): void
    {
        $orderItem = new OrderItem;

        $relation = $orderItem->order();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Order::class, $relation->getRelated()::class);
    }

    /**
     * Test product relation is a BelongsTo instance.
     */
    public function test_product_relation(): void
    {
        $orderItem = new OrderItem;

        $relation = $orderItem->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Product::class, $relation->getRelated()::class);
    }
}
