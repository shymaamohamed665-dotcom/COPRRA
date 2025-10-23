<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Helpers\PriceHelper;
use App\Models\Currency;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(PriceHelper::class)]
class PriceHelperTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test currencies
        Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'decimal_places' => 2,
        ]);

        Currency::create([
            'code' => 'EUR',
            'name' => 'Euro',
            'symbol' => 'â‚¬',
            'exchange_rate' => 0.85,
            'decimal_places' => 2,
        ]);

        Currency::create([
            'code' => 'SAR',
            'name' => 'Saudi Riyal',
            'symbol' => 'Ø±.Ø³',
            'exchange_rate' => 3.75,
            'decimal_places' => 2,
        ]);
    }

    public function test_it_formats_price_with_default_currency(): void
    {
        $result = PriceHelper::formatPrice(100.50);

        $this->assertIsString($result);
        $this->assertStringContainsString('100.50', $result);
        $this->assertStringContainsString('$', $result);
    }

    public function test_it_formats_price_with_specific_currency(): void
    {
        $result = PriceHelper::formatPrice(100.50, 'EUR');

        $this->assertIsString($result);
        $this->assertStringContainsString('100.50', $result);
        $this->assertStringContainsString('â‚¬', $result);
    }

    public function test_it_formats_price_with_sar_currency(): void
    {
        $result = PriceHelper::formatPrice(100.50, 'SAR');

        $this->assertIsString($result);
        $this->assertStringContainsString('100.50', $result);
        $this->assertStringContainsString('Ø±.Ø³', $result);
    }

    public function test_it_handles_non_existent_currency(): void
    {
        $result = PriceHelper::formatPrice(100.50, 'XYZ');

        $this->assertIsString($result);
        $this->assertStringContainsString('100.50', $result);
        $this->assertStringContainsString('XYZ', $result);
    }

    public function test_it_calculates_price_difference_percentage(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 120.0);

        $this->assertIsFloat($result);
        $this->assertEquals(20.0, $result);
    }

    public function test_it_calculates_negative_price_difference(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 80.0);

        $this->assertIsFloat($result);
        $this->assertEquals(-20.0, $result);
    }

    public function test_it_returns_zero_for_same_prices(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 100.0);

        $this->assertEquals(0.0, $result);
    }

    public function test_it_handles_zero_original_price(): void
    {
        $result = PriceHelper::calculatePriceDifference(0.0, 100.0);

        $this->assertEquals(0.0, $result);
    }

    public function test_it_handles_negative_original_price(): void
    {
        $result = PriceHelper::calculatePriceDifference(-10.0, 100.0);

        $this->assertEquals(0.0, $result);
    }

    public function test_it_formats_positive_price_difference_string(): void
    {
        $result = PriceHelper::getPriceDifferenceString(100.0, 120.0);

        $this->assertIsString($result);
        $this->assertStringContainsString('+', $result);
        $this->assertStringContainsString('20.0', $result);
        $this->assertStringContainsString('%', $result);
    }

    public function test_it_formats_negative_price_difference_string(): void
    {
        $result = PriceHelper::getPriceDifferenceString(100.0, 80.0);

        $this->assertIsString($result);
        $this->assertStringContainsString('-20.0', $result);
        $this->assertStringContainsString('%', $result);
    }

    public function test_it_formats_zero_price_difference_string(): void
    {
        $result = PriceHelper::getPriceDifferenceString(100.0, 100.0);

        $this->assertEquals('0%', $result);
    }

    public function test_it_identifies_good_deal(): void
    {
        $allPrices = [100.0, 110.0, 120.0, 130.0];
        $result = PriceHelper::isGoodDeal(95.0, $allPrices);

        $this->assertTrue($result);
    }

    public function test_it_identifies_not_good_deal(): void
    {
        $allPrices = [100.0, 110.0, 120.0, 130.0];
        $result = PriceHelper::isGoodDeal(120.0, $allPrices);

        $this->assertFalse($result);
    }

    public function test_it_handles_empty_prices_array_for_good_deal(): void
    {
        $result = PriceHelper::isGoodDeal(100.0, []);

        $this->assertFalse($result);
    }

    public function test_it_gets_best_price_from_array(): void
    {
        $prices = [100.0, 80.0, 120.0, 90.0];
        $result = PriceHelper::getBestPrice($prices);

        $this->assertEquals(80.0, $result);
    }

    public function test_it_returns_null_for_empty_prices_array(): void
    {
        $result = PriceHelper::getBestPrice([]);

        $this->assertNull($result);
    }

    public function test_it_converts_currency(): void
    {
        $result = PriceHelper::convertCurrency(100.0, 'USD', 'EUR');

        $this->assertIsFloat($result);
        // USD to EUR: 100 / 1.0 * 0.85 = 85.0
        $this->assertEquals(85.0, $result);
    }

    public function test_it_converts_currency_to_sar(): void
    {
        $result = PriceHelper::convertCurrency(100.0, 'USD', 'SAR');

        $this->assertIsFloat($result);
        // USD to SAR: 100 / 1.0 * 3.75 = 375.0
        $this->assertEquals(375.0, $result);
    }

    public function test_it_formats_price_range_with_same_prices(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 100.0, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('$', $result);
        $this->assertStringContainsString('100.00', $result);
        $this->assertStringNotContainsString('-', $result);
    }

    public function test_it_formats_price_range_with_different_prices(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 200.0, 'USD');

        $this->assertIsString($result);
        $this->assertStringContainsString('$', $result);
        $this->assertStringContainsString('100.00', $result);
        $this->assertStringContainsString('200.00', $result);
        $this->assertStringContainsString('-', $result);
    }

    public function test_it_formats_price_range_with_default_currency(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 200.0);

        $this->assertIsString($result);
        $this->assertStringContainsString('$', $result);
    }

    public function test_it_formats_price_range_with_non_existent_currency(): void
    {
        $result = PriceHelper::formatPriceRange(100.0, 200.0, 'XYZ');

        $this->assertIsString($result);
        $this->assertStringContainsString('XYZ', $result);
        $this->assertStringContainsString('100.00', $result);
        $this->assertStringContainsString('200.00', $result);
    }

    public function test_it_handles_decimal_prices_correctly(): void
    {
        $result = PriceHelper::formatPrice(99.99, 'USD');

        $this->assertStringContainsString('99.99', $result);
    }

    public function test_it_handles_large_prices(): void
    {
        $result = PriceHelper::formatPrice(9999999.99, 'USD');

        $this->assertStringContainsString('9,999,999.99', $result);
    }

    public function test_it_calculates_accurate_percentage_for_small_differences(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 101.0);

        $this->assertEquals(1.0, $result);
    }

    public function test_it_calculates_accurate_percentage_for_large_differences(): void
    {
        $result = PriceHelper::calculatePriceDifference(100.0, 200.0);

        $this->assertEquals(100.0, $result);
    }
}
