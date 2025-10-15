<?php

declare(strict_types=1);

namespace App\Services\PriceUpdate;

use App\Models\PriceOffer;

/**
 * Service responsible for fetching prices from external APIs or simulating API calls.
 */
final class PriceFetcherService
{
    /**
     * Fetch price from external API (placeholder implementation).
     */
    public function fetchPriceFromAPI(PriceOffer $priceOffer): ?float
    {
        // This is a placeholder implementation
        // In a real application, you would call the store's API here

        $newPrice = $this->simulateApiCall($priceOffer);

        // Only return if price changed significantly (more than 1%)
        $currentPrice = is_numeric($priceOffer->price) ? (float) $priceOffer->price : 0.0;
        if ($currentPrice > 0 && $newPrice !== null && abs($currentPrice - $newPrice) / $currentPrice > 0.01) {
            return round($newPrice, 2);
        }

        return null;
    }

    /**
     * Simulate an API call to fetch the price.
     */
    private function simulateApiCall(PriceOffer $priceOffer): float
    {
        // Simulate API call with random price fluctuation
        $fluctuation = random_int(-10, 10) / 100; // ±10%
        $currentPrice = is_numeric($priceOffer->price) ? (float) $priceOffer->price : 0.0;

        return $currentPrice * (1 + $fluctuation);
    }
}
