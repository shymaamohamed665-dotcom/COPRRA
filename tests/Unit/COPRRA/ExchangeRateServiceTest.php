<?php

declare(strict_types=1);

namespace Tests\Unit\COPRRA;

use App\Models\ExchangeRate;
use App\Services\ExchangeRates\RateProvider;
use App\Services\ExchangeRateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\CoversClass;
use Tests\TestCase;

#[CoversClass(\App\Services\ExchangeRateService::class)]
#[CoversClass(\App\Models\ExchangeRate::class)]
class ExchangeRateServiceTest extends TestCase
{
    use RefreshDatabase;

    protected ExchangeRateService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new ExchangeRateService(new RateProvider);
        Cache::flush();
    }

    public function test_it_returns_one_for_same_currency(): void
    {
        $rate = $this->service->getRate('USD', 'USD');

        $this->assertEquals(1.0, $rate);
    }

    public function test_it_seeds_exchange_rates_from_config(): void
    {
        $count = $this->service->seedFromConfig();

        $this->assertGreaterThan(0, $count);

        // Check if rates were stored
        $this->assertDatabaseHas('exchange_rates', [
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'source' => 'config',
        ]);
    }

    public function test_it_gets_rate_from_database(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $rate = $this->service->getRate('USD', 'EUR');

        $this->assertEquals(0.85, $rate);
    }

    public function test_it_caches_exchange_rates(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        // First call - should cache
        $rate1 = $this->service->getRate('USD', 'EUR');

        // Check cache
        $cached = Cache::get('exchange_rate_USD_EUR');
        $this->assertNotNull($cached);
        $this->assertEquals(0.85, $cached);

        // Second call - should use cache
        $rate2 = $this->service->getRate('USD', 'EUR');

        $this->assertEquals($rate1, $rate2);
    }

    public function test_it_converts_currency_correctly(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $converted = $this->service->convert(100.0, 'USD', 'EUR');

        $this->assertEquals(85.0, $converted);
    }

    public function test_it_identifies_stale_rates(): void
    {
        $freshRate = ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $staleRate = ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => 0.73,
            'source' => 'test',
            'fetched_at' => now()->subHours(25),
        ]);

        $this->assertFalse($freshRate->isStale());
        $this->assertTrue($staleRate->isStale());
    }

    public function test_it_gets_fresh_rates_only(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => 0.73,
            'source' => 'test',
            'fetched_at' => now()->subHours(25),
        ]);

        $freshRates = ExchangeRate::where('fetched_at', '>', now()->subHours(24))->get();

        $this->assertCount(1, $freshRates);
        $this->assertEquals('EUR', $freshRates->first()->to_currency);
    }

    public function test_it_gets_stale_rates_only(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'GBP',
            'rate' => 0.73,
            'source' => 'test',
            'fetched_at' => now()->subHours(25),
        ]);

        $staleRates = ExchangeRate::stale()->get();

        $this->assertCount(1, $staleRates);
        $this->assertEquals('GBP', $staleRates->first()->to_currency);
    }

    public function test_it_returns_supported_currencies(): void
    {
        $currencies = $this->service->getSupportedCurrencies();

        $this->assertIsArray($currencies);
        $this->assertContains('USD', $currencies);
        $this->assertContains('EUR', $currencies);
        $this->assertContains('SAR', $currencies);
    }

    public function test_it_uses_fallback_rate_when_not_in_database(): void
    {
        // Don't seed any rates
        $rate = $this->service->getRate('USD', 'EUR');

        // Should use fallback from config
        $this->assertIsFloat($rate);
        $this->assertGreaterThan(0, $rate);
    }

    public function test_it_handles_reverse_conversion(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        ExchangeRate::create([
            'from_currency' => 'EUR',
            'to_currency' => 'USD',
            'rate' => 1.176,
            'source' => 'test',
            'fetched_at' => now(),
        ]);

        $usdToEur = $this->service->convert(100.0, 'USD', 'EUR');
        $eurToUsd = $this->service->convert(85.0, 'EUR', 'USD');

        $this->assertEquals(85.0, $usdToEur);
        $this->assertEqualsWithDelta(100.0, $eurToUsd, 0.1);
    }

    public function test_it_updates_existing_rates(): void
    {
        ExchangeRate::create([
            'from_currency' => 'USD',
            'to_currency' => 'EUR',
            'rate' => 0.85,
            'source' => 'test',
            'fetched_at' => now()->subDay(),
        ]);

        $this->service->seedFromConfig();

        $rate = ExchangeRate::where('from_currency', 'USD')
            ->where('to_currency', 'EUR')
            ->first();

        $this->assertNotNull($rate);
        $this->assertEquals('config', $rate->source);
    }
}
