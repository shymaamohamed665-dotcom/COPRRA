<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Mockery;
use Tests\TestCase;

/**
 * Unit tests for the Payment model.
 *
 * @covers \App\Models\Payment
 */

/**
 * @runTestsInSeparateProcesses
 */
class PaymentTest extends TestCase
{
    /**
     * Test that order relation is a BelongsTo instance.
     */
    public function test_order_relation(): void
    {
        $payment = new Payment;

        $relation = $payment->order();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Order::class, $relation->getRelated()::class);
    }

    /**
     * Test that paymentMethod relation is a BelongsTo instance.
     */
    public function test_payment_method_relation(): void
    {
        $payment = new Payment;

        $relation = $payment->paymentMethod();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(PaymentMethod::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeByStatus adds where clause for status.
     */
    public function test_scope_by_status(): void
    {
        $status = 'completed';
        $query = Mockery::mock(\Illuminate\Database\Eloquent\Builder::class);
        $query->shouldReceive('where')->once()->with('status', $status)->andReturnSelf();

        $payment = new Payment;
        $result = $payment->newQuery()->byStatus($status);

        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Builder::class, $result);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
