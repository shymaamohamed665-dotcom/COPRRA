<?php

declare(strict_types=1);

namespace Tests\Unit\DataAccuracy;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceAccuracyTest extends TestCase
{
    use RefreshDatabase;

    // \PHPUnit\Framework\Attributes\Test
    public function test_base_price_calculation(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);
        $this->assertEquals(100.00, $product->price);
    }

    // \PHPUnit\Framework\Attributes\Test
    public function test_tax_inclusive_pricing(): void
    {
        $product = Product::factory()->create([
            'price' => 100.00,
        ]);
        $taxRate = 0.15;

        $this->assertEquals(115.00, round($product->price * (1 + $taxRate), 2));
    }

    // \PHPUnit\Framework\Attributes\Test
    public function test_discount_application_accuracy(): void
    {
        $product = Product::factory()->create([
            'price' => 200.00,
        ]);
        $discount = 0.25;

        $this->assertEquals(150.00, $product->price * (1 - $discount));
    }
}
