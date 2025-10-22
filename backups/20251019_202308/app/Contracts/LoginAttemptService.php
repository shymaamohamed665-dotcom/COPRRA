<?php

declare(strict_types=1);

namespace App\Contracts;

use Illuminate\Http\Request;

interface LoginAttemptService
{
    /**
     * Record a failed login attempt.
     */
    public function recordFailedAttempt(Request $request, ?string $email = null): void;

    /**
     * Record a successful login attempt.
     */
    public function recordSuccessfulAttempt(Request $request, string $email): void;

    /**
     * Check if login is blocked for email.
     */
    public function isEmailBlocked(string $email): bool;

    /**
     * Check if login is blocked for IP.
     */
    public function isIpBlocked(string $ip): bool;

    /**
     * Get remaining attempts for email.
     */
    public function getRemainingAttempts(string $email): int;

    /**
     * Get remaining attempts for IP.
     */
    public function getRemainingIpAttempts(string $ip): int;

    /**
     * Get lockout time remaining for email.
     */
    public function getLockoutTimeRemaining(string $email): ?int;

    /**
     * Get lockout time remaining for IP.
     */
    public function getIpLockoutTimeRemaining(string $ip): ?int;

    /**
     * Unblock email.
     */
    public function unblockEmail(string $email): void;

    /**
     * Unblock IP.
     */
    public function unblockIp(string $ip): void;

    /**
     * Get login attempt statistics.
     *
     * @return array<string, int>
     */
    public function getStatistics(): array;
}
