<?php

declare(strict_types=1);

namespace App\Services\ExchangeRates;

use App\Models\ExchangeRate;
use Illuminate\Support\Facades\Cache;

class RateProvider
{
    private const CACHE_DURATION = 86400;

    public function getRate(string $from, string $to): ?float
    {
        if ($from === $to) {
            return 1.0;
        }

        $rate = $this->fromCache($from, $to) ?? $this->fromDatabase($from, $to);

        return $rate ? $rate : null;
    }

    private function fromCache(string $from, string $to): ?float
    {
        $cacheKey = "exchange_rate_{$from}_{$to}";
        $cachedRate = Cache::get($cacheKey);

        if (is_numeric($cachedRate)) {
            return (float) $cachedRate;
        }

        if ($cachedRate !== null) {
            Cache::forget($cacheKey);
        }

        return null;
    }

    private function fromDatabase(string $from, string $to): ?float
    {
        $rate = ExchangeRate::getRate($from, $to);

        if ($rate !== null) {
            Cache::put("exchange_rate_{$from}_{$to}", $rate, self::CACHE_DURATION);

            return $rate;
        }

        return null;
    }
}
