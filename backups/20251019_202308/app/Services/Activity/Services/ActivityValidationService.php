<?php

declare(strict_types=1);

namespace App\Services\Activity\Services;

/**
 * Service for validating activity data
 */
class ActivityValidationService
{
    /**
     * Validate input data for activity checking
     *
     * @param  array<string, mixed>  $data
     */
    public function isValidInput(array $data): bool
    {
        if (! isset($data['user_id']) || ! is_numeric($data['user_id'])) {
            return false;
        }

        return isset($data['ip_address']) && is_string($data['ip_address']);
    }

    /**
     * Extract user ID from data
     *
     * @param  array<string, mixed>|\Traversable<string, mixed>  $data
     */
    public function extractUserId(array|\Traversable $data): int
    {
        if (is_array($data)) {
            return (int) ($data['user_id'] ?? 0);
        }

        // Handle iterable data
        foreach ($data as $key => $value) {
            if ($key === 'user_id') {
                return (int) $value;
            }
        }

        return 0;
    }

    /**
     * Extract IP address from data
     *
     * @param  array<string, mixed>|\Traversable<string, mixed>  $data
     */
    public function extractIpAddress(array|\Traversable $data): string
    {
        if (is_array($data)) {
            return (string) ($data['ip_address'] ?? '');
        }

        // Handle iterable data
        foreach ($data as $key => $value) {
            if ($key === 'ip_address') {
                return (string) $value;
            }
        }

        return '';
    }

    /**
     * Extract location from data
     *
     * @param  array<string, mixed>|\Traversable<string, mixed>  $data
     * @return array<string, float>
     */
    public function extractLocation(array|\Traversable $data): array
    {
        if (is_array($data) && isset($data['location']) && is_array($data['location'])) {
            return $data['location'];
        }

        return [];
    }

    /**
     * Validate monitoring rule
     *
     * @param  array<string, mixed>  $rule
     */
    public function isRuleEnabled(array $rule): bool
    {
        return (bool) ($rule['enabled'] ?? false);
    }

    /**
     * Get rule configuration with defaults
     *
     * @param  array<string, mixed>  $rule
     * @return array{enabled: bool, time_window: int, threshold: int}
     */
    public function getRuleConfig(array $rule): array
    {
        return [
            'enabled' => (bool) ($rule['enabled'] ?? false),
            'time_window' => (int) ($rule['time_window'] ?? 15),
            'threshold' => (int) ($rule['threshold'] ?? 5),
        ];
    }
}
