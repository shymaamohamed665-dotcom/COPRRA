<?php

namespace Tests\Unit\DataAccuracy;

use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PriceHistoryAccuracyTest extends TestCase
{
    use RefreshDatabase;

    // \[\PHPUnit\Framework\Attributes\Test]
    public function test_price_history_records_changes(): void
    {
        $product = Product::factory()->create(['price' => 100.00]);

        $product->update(['price' => 120.00]);
        $product->update(['price' => 110.00]);

        $this->assertCount(3, $product->priceHistory);
        $this->assertEquals(100.00, $product->priceHistory->first()->price);
        $this->assertEquals(110.00, $product->priceHistory->last()->price);
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function test_historical_prices_accuracy(): void
    {
        $product = Product::factory()->create(['price' => 200.00]);

        $historicalPrices = [
            ['price' => 180.00, 'effective_date' => now()->subDays(3)],
            ['price' => 190.00, 'effective_date' => now()->subDays(1)],
        ];

        $product->priceHistory()->createMany($historicalPrices);

        $this->assertEquals(180.00, $product->priceHistory()->oldest()->first()->price);
        $this->assertEquals(190.00, $product->priceHistory()->where('price', 190.00)->exists());
    }

    // \[\PHPUnit\Framework\Attributes\Test]
    public function test_price_fluctuation_detection(): void
    {
        $product = Product::factory()->create(['price' => 150.00]);

        $product->update(['price' => 135.00]); // -10%
        $product->update(['price' => 148.50]); // +10%

        $this->assertTrue($product->hasSignificantPriceChange(10));
        $this->assertFalse($product->hasSignificantPriceChange(15));
    }
}
