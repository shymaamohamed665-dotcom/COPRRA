<?php

declare(strict_types=1);

namespace App\Services\Activity\Services;

use App\Services\GeolocationService;

/**
 * Service for checking location-based activities
 */
class LocationCheckService
{
    private readonly GeolocationService $geolocationService;

    public function __construct(GeolocationService $geolocationService)
    {
        $this->geolocationService = $geolocationService;
    }

    /**
     * Check if login location is unusual
     *
     * @param  array{enabled: bool, time_window: int, threshold: int}  $rule
     * @param  array<string, float>  $location
     * @return array<array<float>>|null
     *
     * @psalm-return array{current_location: array<string, float>, previous_locations: never}|null
     */
    public function checkUnusualLoginLocation(int $userId, array $location, string $ipAddress, array $rule): ?array
    {
        if (! $rule['enabled']) {
            return null;
        }

        if ($location === []) {
            return null;
        }

        $previousLocations = $this->getUserPreviousLocations();
        if ($previousLocations === [] || ! $this->isLocationUnusual($location, $previousLocations)) {
            return null;
        }

        return [
            'current_location' => $location,
            'previous_locations' => $previousLocations,
        ];
    }

    /**
     * Validate location data
     *
     * @param  array<string, mixed>  $location
     * @return array<float>
     *
     * @psalm-return array{latitude?: float, longitude?: float}
     */
    public function validateLocation(array $location): array
    {
        $validated = [];

        if (isset($location['latitude']) && is_numeric($location['latitude'])) {
            $validated['latitude'] = (float) $location['latitude'];
        }

        if (isset($location['longitude']) && is_numeric($location['longitude'])) {
            $validated['longitude'] = (float) $location['longitude'];
        }

        return $validated;
    }

    /**
     * Check if location is unusual compared to previous locations
     *
     * @param  array<string, float>  $location
     * @param  list<array<string, float>>  $previousLocations
     */
    private function isLocationUnusual(array $location, array $previousLocations): bool
    {
        foreach ($previousLocations as $prevLocation) {
            if ($this->geolocationService->isWithinProximity($location, $prevLocation)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get user's previous locations
     *
     * @psalm-return array<never, never>
     */
    private function getUserPreviousLocations(): array
    {
        // This should be implemented to fetch user's previous locations from a database or cache.
        // For now, return empty array as in original implementation
        return [];
    }
}
