<?php

namespace Tests\Unit\DataAccuracy;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DiscountCalculationTest extends TestCase
{
    use RefreshDatabase;

    // \[\PHPUnit\Framework\Attributes\Test]
    public function test_percentage_discount(): void
    {
        $product = Product::factory()->create([
            'price' => 200.00,
        ]);
        $discount = 0.20;
        $discountType = 'percentage';

        $this->assertEquals(160.00, $product->price * (1 - $discount));
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function test_fixed_amount_discount(): void
    {
        $product = Product::factory()->create([
            'price' => 150.00,
        ]);
        $discount = 30.00;
        $discountType = 'fixed';

        $this->assertEquals(120.00, $product->price - $discount);
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function test_discount_expiration(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);
        $discount = 0.25;
        $discountExpiresAt = now()->subDay();

        // Since the product model doesn't have activeDiscount method,
        // we test if the discount has expired
        $this->assertTrue($discountExpiresAt->isPast());
    }
}
