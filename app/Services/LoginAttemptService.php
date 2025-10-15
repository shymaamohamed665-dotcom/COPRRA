<?php

declare(strict_types=1);

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

final class LoginAttemptService
{
    private const MAX_ATTEMPTS = 5;

    private const LOCKOUT_DURATION = 15; // minutes

    /**
     * Get all blocked emails.
     *
     * @psalm-return array<never, never>
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
     * @psalm-return array<never, never>
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
     * @return int[]
     *
     * @psalm-return array{max_attempts: 5, lockout_duration: 15, blocked_emails_count: 0, blocked_ips_count: 0}
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

    /**
     * Check if an email is blocked due to excessive login attempts.
     */
    public function isEmailBlocked(string $email): bool
    {
        $attempts = Cache::get("login_attempts:$email", []);

        return is_array($attempts) && count($attempts) >= self::MAX_ATTEMPTS;
    }

    /**
     * Check if an IP is blocked due to excessive login attempts.
     */
    public function isIpBlocked(string $ip): bool
    {
        $attempts = Cache::get("ip_attempts:$ip", []);

        return is_array($attempts) && count($attempts) >= self::MAX_ATTEMPTS;
    }

    /**
     * Get remaining attempts for a given email before lockout.
     */
    public function getRemainingAttempts(string $email): int
    {
        $attempts = Cache::get("login_attempts:$email", []);
        $count = is_array($attempts) ? count($attempts) : 0;

        return max(0, self::MAX_ATTEMPTS - $count);
    }

    /**
     * Get remaining attempts for a given IP before lockout.
     */
    public function getRemainingIpAttempts(string $ip): int
    {
        $attempts = Cache::get("ip_attempts:$ip", []);
        $count = is_array($attempts) ? count($attempts) : 0;

        return max(0, self::MAX_ATTEMPTS - $count);
    }

    /**
     * Get lockout time remaining in minutes for an email.
     * Returns null if not locked out or invalid/missing timestamp.
     */
    public function getLockoutTimeRemaining(string $email): ?int
    {
        $attempts = Cache::get("login_attempts:$email", []);
        if (! is_array($attempts) || count($attempts) < self::MAX_ATTEMPTS) {
            return null;
        }

        // Use the most recent attempt timestamp when available
        $latest = end($attempts);
        if (! is_array($latest) || ! array_key_exists('timestamp', $latest)) {
            return null;
        }
        $ts = $latest['timestamp'];
        if (! is_string($ts)) {
            return null;
        }

        // Let Carbon throw on malformed formats as tests expect
        $lockoutUntil = Carbon::parse($ts);
        $remaining = (int) now()->diffInMinutes($lockoutUntil, false);

        return $remaining > 0 ? $remaining : null;
    }

    /**
     * Get lockout time remaining in minutes for an IP.
     * Returns null if not locked out or invalid/missing timestamp.
     */
    public function getIpLockoutTimeRemaining(string $ip): ?int
    {
        $attempts = Cache::get("ip_attempts:$ip", []);
        if (! is_array($attempts) || count($attempts) < self::MAX_ATTEMPTS) {
            return null;
        }

        $latest = end($attempts);
        if (! is_array($latest) || ! array_key_exists('timestamp', $latest)) {
            return null;
        }
        $ts = $latest['timestamp'];
        if (! is_string($ts)) {
            return null;
        }

        $lockoutUntil = Carbon::parse($ts);
        $remaining = (int) now()->diffInMinutes($lockoutUntil, false);

        return $remaining > 0 ? $remaining : null;
    }

    /**
     * Unblock an email (clear attempts) and log action.
     */
    public function unblockEmail(string $email): void
    {
        Cache::forget("login_attempts:$email");
        Log::info('Email unblocked', ['email' => $email]);
    }

    /**
     * Unblock an IP (clear attempts) and log action.
     */
    public function unblockIp(string $ip): void
    {
        Cache::forget("ip_attempts:$ip");
        Log::info('IP unblocked', ['ip' => $ip]);
    }

    /**
     * Record a failed login attempt (IP and optional email) and log it.
     */
    public function recordFailedAttempt(\Illuminate\Http\Request $request, ?string $email = null): void
    {
        $ip = $request->ip();
        $userAgent = $request->userAgent();
        $expiresAt = now()->addMinutes(self::LOCKOUT_DURATION);

        // IP attempts
        $ipKey = "ip_attempts:$ip";
        $ipAttempts = Cache::get($ipKey, []);
        if (! is_array($ipAttempts)) {
            $ipAttempts = [];
        }
        $ipAttempts[] = [
            'timestamp' => $expiresAt->toISOString(),
            'ip' => $ip,
            'user_agent' => $userAgent,
        ];
        Cache::put($ipKey, $ipAttempts, $expiresAt);

        // Email attempts
        if ($email !== null) {
            $emailKey = "login_attempts:$email";
            $emailAttempts = Cache::get($emailKey, []);
            if (! is_array($emailAttempts)) {
                $emailAttempts = [];
            }
            $emailAttempts[] = [
                'timestamp' => $expiresAt->toISOString(),
                'email' => $email,
                'ip' => $ip,
                'user_agent' => $userAgent,
            ];
            Cache::put($emailKey, $emailAttempts, $expiresAt);
        }

        Log::warning('Failed login attempt', [
            'email' => $email,
            'ip' => $ip,
            'user_agent' => $userAgent,
        ]);
    }

    /**
     * Record a successful login (clear attempts) and log it.
     */
    public function recordSuccessfulAttempt(\Illuminate\Http\Request $request, ?string $email = null): void
    {
        $ip = $request->ip();
        if ($email !== null) {
            Cache::forget("login_attempts:$email");
        }
        Cache::forget("ip_attempts:$ip");

        Log::info('Successful login', [
            'email' => $email,
            'ip' => $ip,
        ]);
    }
}
