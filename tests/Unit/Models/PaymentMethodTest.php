<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\Payment;
use App\Models\PaymentMethod;
use Illuminate\Database\Eloquent\Relations\HasMany;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

/**
 * Unit tests for the PaymentMethod model.
 */
#[CoversClass(PaymentMethod::class)]
class PaymentMethodTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'name',
            'gateway',
            'type',
            'config',
            'is_active',
            'is_default',
        ];

        $this->assertEquals($fillable, (new PaymentMethod)->getFillable());
    }

    /**
     * Test casts.
     */
    public function test_casts(): void
    {
        $casts = [
            'config' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
        ];

        $this->assertEquals($casts, (new PaymentMethod)->getCasts());
    }

    /**
     * Test payments relation is a HasMany instance.
     */
    public function test_payments_relation(): void
    {
        $paymentMethod = new PaymentMethod;

        $relation = $paymentMethod->payments();

        $this->assertInstanceOf(HasMany::class, $relation);
        $this->assertEquals(Payment::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive applies correct where clause.
     */
    public function test_scope_active(): void
    {
        $query = PaymentMethod::query()->active();

        $this->assertEquals('select * from "payment_methods" where "is_active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    /**
     * Test scopeDefault applies correct where clause.
     */
    public function test_scope_default(): void
    {
        $query = PaymentMethod::query()->default();

        $this->assertEquals('select * from "payment_methods" where "is_default" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }
}
