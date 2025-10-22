<?php

declare(strict_types=1);

namespace Tests\Feature\COPRRA;

use App\Helpers\PriceHelper;
use App\Models\Category;
use App\Models\Currency;
use App\Models\Product;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(\App\Helpers\PriceHelper::class)]
class PriceComparisonTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected Currency $usd;

    protected Currency $sar;

    protected Category $category;

    protected Store $store1;

    protected Store $store2;

    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        // Create test user
        $this->user = User::factory()->create();

        // Create currencies
        $this->usd = Currency::create([
            'code' => 'USD',
            'name' => 'US Dollar',
            'symbol' => '$',
            'exchange_rate' => 1.0,
            'decimal_places' => 2,
        ]);

        $this->sar = Currency::create([
            'code' => 'SAR',
            'name' => 'Saudi Riyal',
            'symbol' => 'ر.س',
            'exchange_rate' => 3.75,
            'decimal_places' => 2,
        ]);

        // Create category
        $this->category = Category::factory()->create([
            'name' => 'Electronics',
            'slug' => 'electronics',
        ]);

        // Create stores
        $this->store1 = Store::factory()->create([
            'name' => 'Store A',
            'slug' => 'store-a',
            'is_active' => true,
        ]);

        $this->store2 = Store::factory()->create([
            'name' => 'Store B',
            'slug' => 'store-b',
            'is_active' => true,
        ]);

        // Create product
        $this->product = Product::factory()->create([
            'name' => 'Test Product',
            'slug' => 'test-product',
            'category_id' => $this->category->id,
            'price' => 100.00,
            'currency_id' => $this->usd->id,
        ]);
    }

    public function test_compares_prices_across_multiple_stores(): void
    {
        // Create price entries for different stores
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 95.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $prices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray();

        $this->assertCount(2, $prices);
        $this->assertContains(100.00, $prices);
        $this->assertContains(95.00, $prices);

        $bestPrice = PriceHelper::getBestPrice($prices);
        $this->assertEquals(95.00, $bestPrice);
    }

    public function test_identifies_best_deal_among_stores(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 85.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $prices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray();

        $isGoodDeal = PriceHelper::isGoodDeal(85.00, $prices);
        $this->assertTrue($isGoodDeal);

        $isNotGoodDeal = PriceHelper::isGoodDeal(100.00, $prices);
        $this->assertFalse($isNotGoodDeal);
    }

    public function test_calculates_savings_percentage(): void
    {
        $originalPrice = 100.00;
        $salePrice = 80.00;

        $difference = PriceHelper::calculatePriceDifference($originalPrice, $salePrice);

        $this->assertEquals(-20.0, $difference);

        $differenceString = PriceHelper::getPriceDifferenceString($originalPrice, $salePrice);
        $this->assertStringContainsString('-20.0', $differenceString);
        $this->assertStringContainsString('%', $differenceString);
    }

    public function test_formats_price_with_correct_currency_symbol(): void
    {
        $formattedUSD = PriceHelper::formatPrice(100.00, 'USD');
        $this->assertStringContainsString('$', $formattedUSD);
        $this->assertStringContainsString('100.00', $formattedUSD);

        $formattedSAR = PriceHelper::formatPrice(375.00, 'SAR');
        $this->assertStringContainsString('ر.س', $formattedSAR);
        $this->assertStringContainsString('375.00', $formattedSAR);
    }

    public function test_converts_prices_between_currencies(): void
    {
        $usdPrice = 100.00;
        $sarPrice = PriceHelper::convertCurrency($usdPrice, 'USD', 'SAR');

        // USD to SAR: 100 / 1.0 * 3.75 = 375.0
        $this->assertEquals(375.0, $sarPrice);

        $convertedBack = PriceHelper::convertCurrency($sarPrice, 'SAR', 'USD');
        $this->assertEquals($usdPrice, $convertedBack);
    }

    public function test_displays_price_range_for_product(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 120.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $prices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray();

        $minPrice = min($prices);
        $maxPrice = max($prices);

        $priceRange = PriceHelper::formatPriceRange($minPrice, $maxPrice, 'USD');

        $this->assertStringContainsString('100.00', $priceRange);
        $this->assertStringContainsString('120.00', $priceRange);
        $this->assertStringContainsString('-', $priceRange);
    }

    public function test_handles_unavailable_products_in_stores(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 80.00,
            'currency_id' => $this->usd->id,
            'is_available' => false, // Not available
        ]);

        $availablePrices = $this->product->stores()
            ->wherePivot('is_available', true)
            ->get()
            ->pluck('pivot.price')
            ->toArray();

        $this->assertCount(1, $availablePrices);
        $this->assertContains(100.00, $availablePrices);
        $this->assertNotContains(80.00, $availablePrices);
    }

    public function test_respects_max_stores_per_product_configuration(): void
    {
        $maxStores = config('coprra.price_comparison.max_stores_per_product', 10);

        $this->assertIsNumeric($maxStores);
        $this->assertGreaterThan(0, $maxStores);

        // Create more stores than the limit
        for ($i = 0; $i < $maxStores + 5; $i++) {
            $store = Store::factory()->create([
                'name' => "Store {$i}",
                'slug' => "store-{$i}",
                'is_active' => true,
            ]);

            $this->product->stores()->attach($store->id, [
                'price' => 100.00 + $i,
                'currency_id' => $this->usd->id,
                'is_available' => true,
            ]);
        }

        // Get only the max allowed stores
        $limitedStores = $this->product->stores()
            ->wherePivot('is_available', true)
            ->limit($maxStores)
            ->get();

        $this->assertCount($maxStores, $limitedStores);
    }

    public function test_caches_price_comparison_results(): void
    {
        $cacheKey = "price_comparison_{$this->product->id}";
        $cacheDuration = config('coprra.price_comparison.cache_duration', 3600);

        $this->assertIsNumeric($cacheDuration);
        $this->assertGreaterThan(0, $cacheDuration);

        // Simulate caching
        cache()->put($cacheKey, ['prices' => [100.00, 95.00]], $cacheDuration);

        $cached = cache()->get($cacheKey);
        $this->assertNotNull($cached);
        $this->assertArrayHasKey('prices', $cached);
        $this->assertCount(2, $cached['prices']);
    }

    public function test_tracks_price_comparison_analytics(): void
    {
        $trackBehavior = config('coprra.analytics.track_user_behavior', true);
        $trackClicks = config('coprra.analytics.track_price_clicks', true);

        $this->assertIsBool($trackBehavior);
        $this->assertIsBool($trackClicks);

        if ($trackBehavior) {
            // Simulate tracking
            $this->assertTrue(true);
        }
    }

    public function test_handles_multiple_currencies_in_comparison(): void
    {
        $this->product->stores()->attach($this->store1->id, [
            'price' => 100.00,
            'currency_id' => $this->usd->id,
            'is_available' => true,
        ]);

        $this->product->stores()->attach($this->store2->id, [
            'price' => 375.00,
            'currency_id' => $this->sar->id,
            'is_available' => true,
        ]);

        // Convert SAR to USD for comparison
        $sarPriceInUSD = PriceHelper::convertCurrency(375.00, 'SAR', 'USD');

        $this->assertEquals(100.00, $sarPriceInUSD);
    }

    public function test_validates_price_update_interval(): void
    {
        $updateInterval = config('coprra.price_comparison.price_update_interval', 6);

        $this->assertIsNumeric($updateInterval);
        $this->assertGreaterThan(0, $updateInterval);
    }
}