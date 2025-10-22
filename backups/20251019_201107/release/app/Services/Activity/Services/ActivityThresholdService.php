<?php

declare(strict_types=1);

namespace App\Services\Activity\Services;

use Illuminate\Contracts\Cache\Manager as CacheManager;

/**
 * Service for checking activity thresholds
 */
class ActivityThresholdService
{
    private readonly CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    /**
     * Check if threshold is exceeded
     */
    public function checkThreshold(string $key, int $timeWindow, int $threshold): ?int
    {
        $cacheStore = $this->cache->store();
        $count = $cacheStore->increment($key);

        if ($count === 1) {
            $cacheStore->put($key, 1, $timeWindow * 60);
        }

        if ($count < $threshold) {
            return null;
        }

        return $count;
    }

    /**
     * Check multiple failed logins
     *
     * @param  array{enabled: bool, time_window: int, threshold: int}  $rule
     */
    public function checkFailedLogins(int $userId, string $ipAddress, array $rule): ?int
    {
        if (! $rule['enabled']) {
            return null;
        }

        $key = "failed_logins:{$userId}:{$ipAddress}";

        return $this->checkThreshold($key, $rule['time_window'], $rule['threshold']);
    }

    /**
     * Check rapid API requests
     *
     * @param  array{enabled: bool, time_window: int, threshold: int}  $rule
     */
    public function checkRapidApiRequests(int $userId, string $ipAddress, array $rule): ?int
    {
        if (! $rule['enabled']) {
            return null;
        }

        $key = "api_requests:{$userId}:{$ipAddress}";

        return $this->checkThreshold($key, $rule['time_window'], $rule['threshold']);
    }

    /**
     * Check unusual data access
     *
     * @param  array{enabled: bool, time_window: int, threshold: int}  $rule
     */
    public function checkUnusualDataAccess(int $userId, array $rule): ?int
    {
        if (! $rule['enabled']) {
            return null;
        }

        $key = "data_access:{$userId}";

        return $this->checkThreshold($key, $rule['time_window'], $rule['threshold']);
    }

    /**
     * Clear threshold counter
     */
    public function clearThreshold(string $key): void
    {
        $this->cache->store()->forget($key);
    }

    /**
     * Get current count for key
     */
    public function getCurrentCount(string $key): int
    {
        return (int) $this->cache->store()->get($key, 0);
    }
}
