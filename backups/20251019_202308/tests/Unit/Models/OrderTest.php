<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Mockery;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the Order model.
 */
#[CoversClass(Order::class)]
class OrderTest extends TestCase
{
    /**
     * Test that user relation is a BelongsTo instance.
     */
    public function test_user_relation(): void
    {
        $order = new Order;

        $relation = $order->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    /**
     * Test that items relation is a HasMany instance.
     */
    public function test_items_relation(): void
    {
        $order = new Order;

        $relation = $order->items();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(OrderItem::class, $relation->getRelated()::class);
    }

    /**
     * Test that payments relation is a HasMany instance.
     */
    public function test_payments_relation(): void
    {
        $order = new Order;

        $relation = $order->payments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Payment::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeByStatus adds where clause for status.
     */
    public function test_scope_by_status(): void
    {
        $status = 'pending';
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->with('status', $status)->andReturnSelf();

        $order = new Order;
        $result = $order->scopeByStatus($query, $status);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    /**
     * Test scopeForUser adds where clause for user_id.
     */
    public function test_scope_for_user(): void
    {
        $userId = 1;
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->with('user_id', $userId)->andReturnSelf();

        $order = new Order;
        $result = $order->scopeForUser($query, $userId);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
