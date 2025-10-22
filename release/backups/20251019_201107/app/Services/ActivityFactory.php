<?php

declare(strict_types=1);

namespace App\Services;

final class ActivityFactory
{
    /**
     * @param  array{enabled: bool, time_window: int, threshold: int, severity?: string}  $rule
     * @param  array<string, int|string|array<string, string|int|float|bool|array|null>>  $details
     *
     * @return array{type: string, severity: string, user_id: int, details: array<string, int|string|array<string, string|int|float|bool|array|null>>, timestamp: string, ip_address?: string}
     */
    public function create(string $type, int $userId, ?string $ipAddress, iterable $rule, iterable $details): array
    {
        $activity = [
            'type' => $type,
            'severity' => $rule['severity'] ?? 'medium',
            'user_id' => $userId,
            'details' => $details,
            'timestamp' => now()->toISOString(),
        ];

        if ($ipAddress !== null) {
            $activity['ip_address'] = $ipAddress;
        }

        return $activity;
    }
}
