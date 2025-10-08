<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Currency;
use App\Services\ExchangeRateService;

final class PriceHelper
{
    /**
     * Format price with currency symbol.
     */
    public static function formatPrice(float $price, ?string $currencyCode = null): string
    {
        $currencyCode ??= config('coprra.default_currency', 'USD');

        $symbol = self::getCurrencySymbol($currencyCode);

        return $symbol.number_format($price, 2);
    }

    /**
     * Calculate price difference percentage.
     */
    public static function calculatePriceDifference(float $originalPrice, float $comparePrice): float
    {
        if ($originalPrice <= 0) {
            return 0.0;
        }

        return ($comparePrice - $originalPrice) / $originalPrice * 100;
    }

    /**
     * Get price difference as formatted string.
     */
    public static function getPriceDifferenceString(float $originalPrice, float $comparePrice): string
    {
        $difference = self::calculatePriceDifference($originalPrice, $comparePrice);
        if ($difference > 0) {
            return '+'.number_format($difference, 1).'%';
        }

        if ($difference < 0) {
            return number_format($difference, 1).'%';
        }

        return '0%';
    }

    /**
     * Check if price is a good deal (below average).
     *
     * @param  array<float>  $allPrices
     */
    public static function isGoodDeal(float $price, array $allPrices): bool
    {
        if ($allPrices === []) {
            return false;
        }

        $average = array_sum($allPrices) / count($allPrices);

        return $price < $average * 0.9; // 10% below average
    }

    /**
     * Get best price from array of prices.
     *
     * @param  array<float>  $prices
     */
    public static function getBestPrice(array $prices): ?float
    {
        if ($prices === []) {
            return null;
        }

        return min($prices);
    }

    /**
     * Convert price between currencies using database exchange rates.
     */
    public static function convertCurrency(float $amount, string $fromCurrency, string $toCurrency): float
    {
        // Use ExchangeRateService for dynamic rates
        $service = app(ExchangeRateService::class);

        return $service->convert($amount, $fromCurrency, $toCurrency);
    }

    /**
     * Format price range.
     */
    public static function formatPriceRange(float $minPrice, float $maxPrice, ?string $currencyCode = null): string
    {
        $symbol = self::getCurrencySymbol($currencyCode);

        if ($minPrice === $maxPrice) {
            return $symbol.number_format($minPrice, 2);
        }

        return $symbol.number_format($minPrice, 2).' - '.$symbol.number_format($maxPrice, 2);
    }

    private static function getCurrencySymbol(?string $currencyCode): string
    {
        $currencyCode ??= config('coprra.default_currency', 'USD');

        /** @var \App\Models\Currency|null $currency */
        $currency = Currency::where('code', $currencyCode)->first();

        if (! $currency) {
            return is_string($currencyCode) ? $currencyCode : 'USD';
        }

        return (string) $currency->symbol;
    }
}
