<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Models\Currency;
use App\Services\ExchangeRateService;

/**
 */
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

        // Consider a price a good deal if it's strictly below the average
        return $price < $average;
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
        // Prefer Currency model exchange_rate values set in DB/tests
        /** @var \App\Models\Currency|null $from */
        $from = Currency::where('code', $fromCurrency)->first();
        /** @var \App\Models\Currency|null $to */
        $to = Currency::where('code', $toCurrency)->first();

        if ($from && $to && is_numeric($from->exchange_rate) && is_numeric($to->exchange_rate)) {
            $fromRate = (float) $from->exchange_rate;
            $toRate = (float) $to->exchange_rate;

            if ($fromRate > 0) {
                return round($amount / $fromRate * $toRate, 2);
            }
        }

        // Fallback to ExchangeRateService (DB/cache/config/API)
        $service = app(ExchangeRateService::class);

        return $service->convert($amount, $fromCurrency, $toCurrency);
    }

    /**
     * Format price range.
     * Accepts numeric strings or integers and safely casts to float.
     */
    public static function formatPriceRange(float|int|string $minPrice, float|int|string $maxPrice, ?string $currencyCode = null): string
    {
        $symbol = self::getCurrencySymbol($currencyCode);

        $min = is_numeric($minPrice) ? (float) $minPrice : 0.0;
        $max = is_numeric($maxPrice) ? (float) $maxPrice : 0.0;

        if ($min === $max) {
            return $symbol.number_format($min, 2);
        }

        return $symbol.number_format($min, 2).' - '.$symbol.number_format($max, 2);
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
