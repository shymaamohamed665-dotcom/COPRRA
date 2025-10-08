<?php

declare(strict_types=1);

namespace App\Services;

final class LoginAttemptService
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_DURATION = 15; // minutes

    /**
     * Get all blocked emails.
     *
     * @return list<string>
     */
    public function getBlockedEmails(): array
    {
        // This would need to be implemented based on your cache driver
        // For now, return empty array
        return [];
    }

    /**
     * Get all blocked IPs.
     *
     * @return list<string>
     */
    public function getBlockedIps(): array
    {
        // This would need to be implemented based on your cache driver
        // For now, return empty array
        return [];
    }

    /**
     * Get login attempt statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array
    {
        return [
            'max_attempts' => self::MAX_ATTEMPTS,
            'lockout_duration' => self::LOCKOUT_DURATION,
            'blocked_emails_count' => count($this->getBlockedEmails()),
            'blocked_ips_count' => count($this->getBlockedIps()),
        ];
    }
}
