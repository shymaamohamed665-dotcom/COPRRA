<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\ExchangeRate;
use App\Services\ExchangeRates\RateProvider;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

final readonly class ExchangeRateService
{
    /**
     * Base currency for exchange rates.
     */
    private const BASE_CURRENCY = 'USD';

    /**
     * Supported currencies.
     */
    private const SUPPORTED_CURRENCIES = ['USD', 'EUR', 'GBP', 'JPY', 'SAR', 'AED', 'EGP'];

    private \App\Services\ExchangeRates\RateProvider $rateProvider;

    public function __construct(RateProvider $rateProvider)
    {
        $this->rateProvider = $rateProvider;
    }

    /**
     * Get exchange rate between two currencies.
     */
    public function getRate(string $fromCurrency, string $toCurrency): float
    {
        $rate = $this->rateProvider->getRate($fromCurrency, $toCurrency);

        if ($rate !== null) {
            return $rate;
        }

        // Fetch from API if not in database
        $this->fetchAndStoreRate();

        $rate = $this->rateProvider->getRate($fromCurrency, $toCurrency);

        return $rate ?? $this->getFallbackRate($fromCurrency, $toCurrency);
    }

    /**
     * Convert amount from one currency to another.
     */
    public function convert(float $amount, string $fromCurrency, string $toCurrency): float
    {
        $rate = $this->getRate($fromCurrency, $toCurrency);

        return round($amount * $rate, 2);
    }

    /**
     * Fetch exchange rates from external API and store in database.
     *
     * @psalm-return int<0, max>
     */
    public function fetchAndStoreRates(): int
    {
        try {
            $response = $this->fetchRatesFromApi();

            if ($response === null) {
                return 0;
            }

            return $this->handleApiResponse($response);
        } catch (\Exception $e) {
            Log::error('Error fetching exchange rates', [
                'error' => $e->getMessage(),
            ]);
        }

        return 0;
    }

    /**
     * Get supported currencies.
     *
     * @return array<string>
     *
     * @psalm-return list{'USD', 'EUR', 'GBP', 'JPY', 'SAR', 'AED', 'EGP'}
     */
    public function getSupportedCurrencies(): array
    {
        return self::SUPPORTED_CURRENCIES;
    }

    /**
     * Seed initial exchange rates from config.
     *
     * @psalm-return int<0, max>
     */
    public function seedFromConfig(): int
    {
        $count = 0;
        $rates = config('coprra.exchange_rates', []);

        if (! is_array($rates)) {
            Log::warning('Invalid exchange rates configuration');

            return 0;
        }

        foreach ($rates as $currency => $rate) {
            if ($currency === self::BASE_CURRENCY) {
                continue;
            }

            ExchangeRate::updateOrCreate(
                [
                    'from_currency' => self::BASE_CURRENCY,
                    'to_currency' => $currency,
                ],
                [
                    'rate' => $rate,
                    'source' => 'config',
                    'fetched_at' => now(),
                ]
            );

            // Also store reverse rate
            if (is_numeric($rate)) {
                ExchangeRate::updateOrCreate(
                    [
                        'from_currency' => $currency,
                        'to_currency' => self::BASE_CURRENCY,
                    ],
                    [
                        'rate' => 1 / (float) $rate,
                        'source' => 'config',
                        'fetched_at' => now(),
                    ]
                );
            }

            $count += 2;
        }

        Log::info("Seeded {$count} exchange rates from config");

        return $count;
    }

    /**
     * Fetch and store a specific currency pair rate.
     */
    private function fetchAndStoreRate(): void
    {
        // For simplicity, fetch all rates
        $this->fetchAndStoreRates();
    }

    /**
     * Get fallback rate from config.
     */
    private function getFallbackRate(string $fromCurrency, string $toCurrency): float
    {
        $rates = config('coprra.exchange_rates', []);

        if (! is_array($rates)) {
            return 1.0;
        }

        $fromRate = $rates[$fromCurrency] ?? 1.0;
        $toRate = $rates[$toCurrency] ?? 1.0;

        // Ensure numeric values
        if (! is_numeric($fromRate) || ! is_numeric($toRate)) {
            return 1.0;
        }

        // Convert: amount in fromCurrency -> USD -> toCurrency
        return (float) $toRate / (float) $fromRate;
    }

    /**
     * @return array<string, float>|null
     */
    private function fetchRatesFromApi(): ?array
    {
        $apiUrl = config('coprra.exchange_rate_api_url', 'https://api.exchangerate-api.com/v4/latest/USD');
        if (! is_string($apiUrl)) {
            return null;
        }

        $apiKey = config('coprra.exchange_rate_api_key');
        if ($apiKey !== null && is_string($apiKey)) {
            $apiUrl .= "?apikey={$apiKey}";
        }

        $response = Http::timeout(10)->get($apiUrl);

        if (! $response->successful()) {
            Log::warning('Failed to fetch exchange rates from API', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return null;
        }

        return $response->json();
    }

    /**
     * @param  array<string, array<string, float>|string>  $data
     *
     * @psalm-return int<0, max>
     */
    private function handleApiResponse(array $data): int
    {
        if (! isset($data['rates']) || ! is_array($data['rates'])) {
            Log::warning('Invalid exchange rate API response format');

            return 0;
        }

        $baseCurrency = $data['base'] ?? self::BASE_CURRENCY;
        if (! is_string($baseCurrency)) {
            $baseCurrency = self::BASE_CURRENCY;
        }

        $count = 0;
        foreach (self::SUPPORTED_CURRENCIES as $currency) {
            if ($currency === $baseCurrency || ! isset($data['rates'][$currency])) {
                continue;
            }

            $this->storeRate($baseCurrency, $currency, $data['rates'][$currency]);
            $count += 2;
        }

        Log::info("Successfully fetched and stored {$count} exchange rates");

        return $count;
    }

    private function storeRate(string $from, string $toCurrency, float $rate): void
    {
        ExchangeRate::updateOrCreate(
            ['from_currency' => $from, 'to_currency' => $toCurrency],
            ['rate' => $rate, 'source' => 'api', 'fetched_at' => now()]
        );

        if ($rate > 0) {
            ExchangeRate::updateOrCreate(
                ['from_currency' => $toCurrency, 'to_currency' => $from],
                ['rate' => 1 / $rate, 'source' => 'api', 'fetched_at' => now()]
            );
        }

        Cache::forget("exchange_rate_{$from}_{$toCurrency}");
        Cache::forget("exchange_rate_{$toCurrency}_{$from}");
    }
}
