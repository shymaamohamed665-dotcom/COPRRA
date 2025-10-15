<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Contracts\Cache\Manager as CacheManager;

final class ActivityChecker
{
    /**
     * @var array<string, array<string, array<string, int|string>|bool|float|int|string|null>>
     */
    private array $config = [];

    private CacheManager $cache;

    private GeolocationService $geolocationService;

    private ActivityFactory $activityFactory;

    public function __construct(
        CacheManager $cache,
        GeolocationService $geolocationService,
        ActivityFactory $activityFactory,
        ConfigurationService $configurationService
    ) {
        $this->cache = $cache;
        $this->geolocationService = $geolocationService;
        $this->activityFactory = $activityFactory;
        $this->config = $configurationService->getSuspiciousActivityConfig();
    }

    /**
     * @param array{
     *     user_id: int,
     *     ip_address: string,
     *     location?: array<string, float>,
     *     action?: string,
     *     resource?: string,
     *     changes?: array<string, array<string, int|string>|bool|float|int|string|null>
     * }  $data
     * @return App\Services\SuspiciousActivity[]
     *
     * @psalm-return list<App\Services\SuspiciousActivity>
     */
    public function getSuspiciousActivities(string $event, array $data): array
    {
        if (! $this->isValidInput($data)) {
            return [];
        }

        $activityCheckers = $this->getActivityCheckers();

        if (isset($activityCheckers[$event])) {
            return $activityCheckers[$event]($data);
        }

        return [];
    }

    /**
     * Handles API request activities.
     *
     * @param  array<string, array<string, int|string>|bool|float|int|string|null>  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    public function handleApiRequest(array $data): array
    {
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $ipAddress = (isset($data['ip_address']) && is_string($data['ip_address'])) ? $data['ip_address'] : '';

        return $this->checkRapidApiRequests($userId, $ipAddress);
    }

    /**
     * Handles data access activities.
     *
     * @param  array<string, array<string, int|string>|bool|float|int|string|null>  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    public function handleDataAccess(array $data): array
    {
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $ipAddress = (isset($data['ip_address']) && is_string($data['ip_address'])) ? $data['ip_address'] : '';

        $shapeData = ['user_id' => $userId, 'ip_address' => $ipAddress];
        if (isset($data['data_type']) && is_string($data['data_type'])) {
            $shapeData['data_type'] = $data['data_type'];
        }

        return $this->checkUnusualDataAccess($userId, $shapeData);
    }

    /**
     * Handles admin action activities.
     *
     * @param  array<string, array<string, int|string>|bool|float|int|string|null>  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    public function handleAdminAction(array $data): array
    {
        return $this->checkAdminActions($data);
    }

    /**
     * @param  array<string, int|string>  $data
     */
    private function isValidInput(array $data): bool
    {
        if (! isset($data['user_id']) || ! is_numeric($data['user_id'])) {
            return false;
        }

        if (! isset($data['ip_address']) || ! is_string($data['ip_address'])) {
            return false;
        }

        return true;
    }

    /**
     * @return \Closure[]
     *
     * @psalm-return array{login_failed: \Closure(array):list<App\Services\SuspiciousActivity>, login_success: \Closure(array):list<App\Services\SuspiciousActivity>, api_request: \Closure(array):list<App\Services\SuspiciousActivity>, data_access: \Closure(array):list<App\Services\SuspiciousActivity>, admin_action: \Closure(array):list<App\Services\SuspiciousActivity>}
     */
    private function getActivityCheckers(): array
    {
        return [
            'login_failed' => /**
             * @return SuspiciousActivity[]
             *
             * @psalm-return list<App\Services\SuspiciousActivity>
             */
            fn (array $data): array => $this->handleLoginFailed($data),
            'login_success' => /**
             * @return SuspiciousActivity[]
             *
             * @psalm-return list<App\Services\SuspiciousActivity>
             */
            fn (array $data): array => $this->handleLoginSuccess($data),
            'api_request' => /**
             * @return SuspiciousActivity[]
             *
             * @psalm-return list<App\Services\SuspiciousActivity>
             */
            fn (array $data): array => $this->handleApiRequest($data),
            'data_access' => /**
             * @return SuspiciousActivity[]
             *
             * @psalm-return list<App\Services\SuspiciousActivity>
             */
            fn (array $data): array => $this->handleDataAccess($data),
            'admin_action' => /**
             * @return SuspiciousActivity[]
             *
             * @psalm-return list<App\Services\SuspiciousActivity>
             */
            fn (array $data): array => $this->handleAdminAction($data),
        ];
    }

    /**
     * @param array{
     *     user_id: int,
     *     ip_address: string,
     *     location?: array<string, float>,
     *     action?: string,
     *     resource?: string,
     *     changes?: array<string, array<string, int|string>|bool|float|int|string|null>
     * }  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function handleLoginFailed(array $data): array
    {
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $ipAddress = (isset($data['ip_address']) && is_string($data['ip_address'])) ? $data['ip_address'] : '';

        return $this->checkMultipleFailedLogins($userId, $ipAddress);
    }

    /**
     * @param array{
     *     user_id: int,
     *     ip_address: string,
     *     location?: array<string, float>,
     *     action?: string,
     *     resource?: string,
     *     changes?: array<string, array<string, int|string>|bool|float|int|string|null>
     * }  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function handleLoginSuccess(array $data): array
    {
        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $ipAddress = (isset($data['ip_address']) && is_string($data['ip_address'])) ? $data['ip_address'] : '';
        $location = is_array($data['location'] ?? null) ? $data['location'] : [];

        return $this->checkUnusualLoginLocation($userId, $location, $ipAddress);
    }

    /**
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function checkMultipleFailedLogins(int $userId, string $ipAddress): array
    {
        $rule = $this->getMonitoringRule('multiple_failed_logins');

        if (! ($rule['enabled'] ?? false)) {
            return [];
        }

        $key = "failed_logins:{$userId}:{$ipAddress}";
        $timeWindow = (int) ($rule['time_window'] ?? 15);
        $threshold = (int) ($rule['threshold'] ?? 5);

        $failedCount = $this->checkThreshold($key, $timeWindow, $threshold);

        if ($failedCount === null) {
            return [];
        }

        $activityData = [
            'failed_attempts' => $failedCount,
            'time_window' => $timeWindow,
        ];

        return [$this->activityFactory->create('multiple_failed_logins', $userId, $ipAddress, $rule, $activityData)];
    }

    /**
     * @param  array<string, float>  $location
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function checkUnusualLoginLocation(int $userId, array $location, string $ipAddress): array
    {
        $rule = $this->getMonitoringRule('unusual_login_location');

        if (! ($rule['enabled'] ?? false)) {
            return [];
        }

        $previousLocations = $this->getUserPreviousLocations();
        if ($previousLocations === [] || ! $this->isLocationUnusual($location, $previousLocations)) {
            return [];
        }

        $activityData = [
            'current_location' => $location,
            'previous_locations' => $previousLocations,
        ];

        return [$this->activityFactory->create('unusual_login_location', $userId, $ipAddress, $rule, $activityData)];
    }

    /**
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function checkRapidApiRequests(int $userId, string $ipAddress): array
    {
        $rule = $this->getMonitoringRule('rapid_api_requests');

        if (! ($rule['enabled'] ?? false)) {
            return [];
        }

        $key = "api_requests:{$userId}:{$ipAddress}";
        $timeWindow = (int) ($rule['time_window'] ?? 5);
        $threshold = (int) ($rule['threshold'] ?? 100);

        $requestCount = $this->checkThreshold($key, $timeWindow, $threshold);

        if ($requestCount === null) {
            return [];
        }

        $activityData = [
            'request_count' => $requestCount,
            'time_window' => $timeWindow,
        ];

        return [$this->activityFactory->create('rapid_api_requests', $userId, $ipAddress, $rule, $activityData)];
    }

    /**
     * @param  array{user_id: int, ip_address: string, data_type?: string}  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function checkUnusualDataAccess(int $userId, array $data): array
    {
        $rule = $this->getMonitoringRule('unusual_data_access');

        if (! ($rule['enabled'] ?? false)) {
            return [];
        }

        $key = "data_access:{$userId}";
        $timeWindow = (int) ($rule['time_window'] ?? 60);
        $threshold = (int) ($rule['threshold'] ?? 1000);

        $accessCount = $this->checkThreshold($key, $timeWindow, $threshold);

        if ($accessCount === null) {
            return [];
        }

        $details = $this->getUnusualDataAccessDetails($data, $accessCount, $rule);

        return [$this->activityFactory->create(
            'unusual_data_access',
            $userId,
            (string) ($data['ip_address'] ?? ''),
            $rule,
            $details
        ),
        ];
    }

    /**
     * Gets details of unusual data access.
     *
     * @param  iterable<string, array<string, int|string>|bool|float|int|string|null>  $data
     * @param  array{enabled: bool, time_window: int, threshold: int}  $rule
     * @return array{access_count: int, time_window: int, data_type: string}
     */
    private function getUnusualDataAccessDetails(array $data, int $accessCount, iterable $rule): array
    {
        return [
            'access_count' => $accessCount,
            'time_window' => (int) ($rule['time_window'] ?? 60),
            'data_type' => (isset($data['data_type']) && is_string($data['data_type'])) ? $data['data_type'] : 'unknown',
        ];
    }

    /**
     * @param  iterable<string, array<string, int|string>|bool|float|int|string|null>  $data
     * @return (((array|null|scalar)[]|int|string)[]|int|string)[][]
     *
     * @psalm-return list{0?: array{type: string, severity: string, user_id: int, details: array<string, array<string, array|null|scalar>|int|string>, timestamp: string, ip_address?: string}}
     */
    private function checkAdminActions(array $data): array
    {
        $rule = $this->getMonitoringRule('admin_actions');

        if (! ($rule['enabled'] ?? false)) {
            return [];
        }

        $userId = isset($data['user_id']) ? (int) $data['user_id'] : 0;
        $ipAddress = (isset($data['ip_address']) && is_string($data['ip_address'])) ? $data['ip_address'] : '';

        $activityData = [
            'action' => (isset($data['action']) && is_string($data['action'])) ? $data['action'] : 'unknown',
            'resource' => (isset($data['resource']) && is_string($data['resource'])) ? $data['resource'] : 'unknown',
            'changes' => (isset($data['changes']) && is_array($data['changes'])) ? $data['changes'] : [],
        ];

        return [$this->activityFactory->create('admin_action', $userId, $ipAddress, $rule, $activityData)];
    }

    /**
     * @param  array<string, float>  $location
     * @param  list<array<string, float>>  $previousLocations
     */
    private function isLocationUnusual(array $location, array $previousLocations): bool
    {
        if ($location === []) {
            return false;
        }

        foreach ($previousLocations as $prevLocation) {
            if ($this->geolocationService->isWithinProximity($location, $prevLocation)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @psalm-return array<never, never>
     */
    private function getUserPreviousLocations(): array
    {
        // This should be implemented to fetch user's previous locations from a database or cache.
        return [];
    }

    private function checkThreshold(string $key, int $timeWindow, int $threshold): ?int
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
     * @return (int|string)[]|scalar
     *
     * @psalm-return array<string, int|string>|scalar
     */
    private function getMonitoringRule(string $ruleName)
    {
        return $this->config['monitoring_rules'][$ruleName] ?? [];
    }
}
