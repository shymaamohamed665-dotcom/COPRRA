<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use App\Models\PriceAlert;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Unit tests for the PriceAlert model.
 */
#[CoversClass(PriceAlert::class)]
class PriceAlertTest extends TestCase
{
    /**
     * Test fillable attributes.
     */
    public function test_fillable_attributes(): void
    {
        $fillable = [
            'user_id',
            'product_id',
            'target_price',
            'repeat_alert',
            'is_active',
        ];

        $this->assertEquals($fillable, (new PriceAlert)->getFillable());
    }

    /**
     * Test casts.
     */
    public function test_casts(): void
    {
        $casts = [
            'target_price' => 'decimal:2',
            'repeat_alert' => 'boolean',
            'is_active' => 'boolean',
        ];

        $this->assertEquals($casts, (new PriceAlert)->getCasts());
    }

    /**
     * Test uses SoftDeletes.
     */
    public function test_uses_soft_deletes(): void
    {
        $this->assertContains('Illuminate\Database\Eloquent\SoftDeletes', class_uses(PriceAlert::class));
    }

    /**
     * Test user relation is a BelongsTo instance.
     */
    public function test_user_relation(): void
    {
        $priceAlert = new PriceAlert;

        $relation = $priceAlert->user();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(User::class, $relation->getRelated()::class);
    }

    /**
     * Test product relation is a BelongsTo instance.
     */
    public function test_product_relation(): void
    {
        $priceAlert = new PriceAlert;

        $relation = $priceAlert->product();

        $this->assertInstanceOf(BelongsTo::class, $relation);
        $this->assertEquals(Product::class, $relation->getRelated()::class);
    }

    /**
     * Test scopeActive applies correct where clause.
     */
    public function test_scope_active(): void
    {
        $query = PriceAlert::query()->active();

        $this->assertEquals('select * from "price_alerts" where "is_active" = ?', $query->toSql());
        $this->assertEquals([true], $query->getBindings());
    }

    /**
     * Test scopeForUser applies correct where clause.
     */
    public function test_scope_for_user(): void
    {
        $query = PriceAlert::query()->forUser(1);

        $this->assertEquals('select * from "price_alerts" where "user_id" = ?', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    /**
     * Test scopeForProduct applies correct where clause.
     */
    public function test_scope_for_product(): void
    {
        $query = PriceAlert::query()->forProduct(1);

        $this->assertEquals('select * from "price_alerts" where "product_id" = ?', $query->toSql());
        $this->assertEquals([1], $query->getBindings());
    }

    /**
     * Test getRules returns validation rules.
     */
    public function test_get_rules(): void
    {
        $priceAlert = new PriceAlert;

        $rules = $priceAlert->getRules();

        $this->assertIsArray($rules);
        $this->assertArrayHasKey('user_id', $rules);
        $this->assertArrayHasKey('product_id', $rules);
        $this->assertArrayHasKey('target_price', $rules);
    }

    /**
     * Test isPriceTargetReached returns true when current price is less than or equal to target.
     */
    public function test_is_price_target_reached_true(): void
    {
        $priceAlert = new PriceAlert(['target_price' => 100.00]);

        $this->assertTrue($priceAlert->isPriceTargetReached(90.00));
        $this->assertTrue($priceAlert->isPriceTargetReached(100.00));
    }

    /**
     * Test isPriceTargetReached returns false when current price is greater than target.
     */
    public function test_is_price_target_reached_false(): void
    {
        $priceAlert = new PriceAlert(['target_price' => 100.00]);

        $this->assertFalse($priceAlert->isPriceTargetReached(110.00));
    }
}
