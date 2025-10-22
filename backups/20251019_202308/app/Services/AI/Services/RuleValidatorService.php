<?php

declare(strict_types=1);

namespace App\Services\AI\Services;

/**
 * Service for validating monitoring rules and configurations
 */
class RuleValidatorService
{
    /**
     * Validate rule configuration
     *
     * @param  array<string, mixed>  $rule
     */
    public function validateRule(array $rule): bool
    {
        return isset($rule['name'], $rule['threshold'], $rule['command'])
            && is_string($rule['name'])
            && is_numeric($rule['threshold'])
            && is_string($rule['command']);
    }

    /**
     * Validate health score value
     *
     * @psalm-return int<0, 100>
     */
    public function validateHealthScore(mixed $score): int
    {
        if (! is_numeric($score)) {
            return 0;
        }

        $intScore = (int) $score;

        return max(0, min(100, $intScore));
    }

    /**
     * Validate last check timestamp
     */
    public function validateLastCheck(mixed $lastCheck): ?string
    {
        return is_string($lastCheck) && $this->isValidIso8601($lastCheck) ? $lastCheck : null;
    }

    /**
     * Validate detailed results
     *
     * @return array<string, mixed>
     */
    public function validateDetailedResults(mixed $results): array
    {
        return is_array($results) ? $results : [];
    }

    /**
     * Check if string is valid ISO8601 format
     */
    private function isValidIso8601(string $date): bool
    {
        try {
            \Carbon\Carbon::parse($date);

            return true;
        } catch (\Exception) {
            return false;
        }
    }
}
