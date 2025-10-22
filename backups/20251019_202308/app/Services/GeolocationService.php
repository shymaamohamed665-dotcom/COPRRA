<?php

declare(strict_types=1);

namespace App\Services;

final class GeolocationService
{
    /**
     * Calculate the distance between two geo-locations.
     *
     * @param  array<string, float|int|string>  $location1
     * @param  array<string, float|int|string>  $location2
     */
    public function calculateDistance(array $location1, array $location2): float
    {
        $lat1 = $location1['lat'] ?? 0;
        $lon1 = $location1['lon'] ?? 0;
        $lat2 = $location2['lat'] ?? 0;
        $lon2 = $location2['lon'] ?? 0;

        $earthRadius = 6371;

        $latFrom = deg2rad((float) $lat1);
        $lonFrom = deg2rad((float) $lon1);
        $latTo = deg2rad((float) $lat2);
        $lonTo = deg2rad((float) $lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
        $centralAngle = $angle;

        return $earthRadius * $centralAngle;
    }
}
