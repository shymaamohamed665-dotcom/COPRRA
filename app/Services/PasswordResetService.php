<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

final class PasswordResetService
{
    private const TOKEN_EXPIRY = 60; // minutes

    private const MAX_ATTEMPTS = 3;

    private const CACHE_PREFIX = 'password_reset:';

    /**
     * Send password reset email.
     */
    public function sendResetEmail(string $email): bool
    {
        $user = User::where('email', $email)->first();

        if (! $user) {
            Log::warning('Password reset requested for non-existent email', [
                'email' => $email,
            ]);

            return false;
        }

        // Check if user is blocked
        if ($user->is_blocked) {
            Log::warning('Password reset requested for blocked user', [
                'email' => $email,
                'user_id' => $user->id,
            ]);

            return false;
        }

        // Generate reset token
        $token = $this->generateResetToken();

        // Store token in cache
        $this->storeResetToken($email, $token);

        // Send email
        try {
            Mail::send('emails.password-reset', [
                'user' => $user,
                'token' => $token,
                'expiry' => self::TOKEN_EXPIRY,
            ], static function (\Illuminate\Mail\Message $message) use ($user): void {
                $message->to($user->email, $user->name)
                    ->subject('استعادة كلمة المرور - كوبرا');
            });

            Log::info('Password reset email sent', [
                'email' => $email,
                'user_id' => $user->id,
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error('Failed to send password reset email', [
                'email' => $email,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Reset password with token.
     */
    public function resetPassword(string $email, string $token, string $newPassword): bool
    {
        // Validate token
        if (! $this->validateResetToken($email, $token)) {
            Log::warning('Invalid password reset token', [
                'email' => $email,
                'token' => $token,
            ]);

            return false;
        }

        $user = User::where('email', $email)->first();

        if (! $user) {
            return false;
        }

        // Update password
        $user->password = Hash::make($newPassword);
        $user->save();

        // Clear reset token
        $this->clearResetToken($email);

        // Log password reset
        Log::info('Password reset successful', [
            'email' => $email,
            'user_id' => $user->id,
        ]);

        return true;
    }

    /**
     * Check if reset token exists.
     */
    public function hasResetToken(string $email): bool
    {
        $key = self::CACHE_PREFIX.hash('sha256', $email);

        return Cache::has($key);
    }

    /**
     * Get reset token info.
     *
     * @return array<string, string|int|null>|null
     */
    public function getResetTokenInfo(string $email): ?array
    {
        $key = self::CACHE_PREFIX.hash('sha256', $email);
        $data = Cache::get($key);

        if (! $data || ! is_array($data)) {
            return null;
        }

        $createdAt = $data['created_at'] ?? null;
        $attempts = is_numeric($data['attempts'] ?? null) ? (int) ($data['attempts'] ?? 0) : 0;

        return [
            'created_at' => $createdAt,
            'expires_at' => $createdAt && is_string($createdAt) ? Carbon::parse($createdAt)->addMinutes(self::TOKEN_EXPIRY)->toISOString() : null,
            'attempts' => $attempts,
            'remaining_attempts' => self::MAX_ATTEMPTS - $attempts,
        ];
    }

    /**
     * Clean up expired tokens.
     */
    public function cleanupExpiredTokens(): int
    {
        // This would need to be implemented based on your cache driver
        // For now, return 0
        return 0;
    }

    /**
     * Get password reset statistics.
     *
     * @return int[]
     *
     * @psalm-return array{token_expiry_minutes: 60, max_attempts: 3, expired_tokens_cleaned: int}
     */
    public function getStatistics(): array
    {
        return [
            'token_expiry_minutes' => self::TOKEN_EXPIRY,
            'max_attempts' => self::MAX_ATTEMPTS,
            'expired_tokens_cleaned' => $this->cleanupExpiredTokens(),
        ];
    }

    /**
     * Generate reset token.
     */
    private function generateResetToken(): string
    {
        return Str::random(64);
    }

    /**
     * Store reset token.
     */
    private function storeResetToken(string $email, string $token): void
    {
        $key = self::CACHE_PREFIX.hash('sha256', $email);

        $data = [
            'token' => $token,
            'created_at' => now()->toISOString(),
            'attempts' => 0,
        ];

        Cache::put($key, $data, now()->addMinutes(self::TOKEN_EXPIRY));
    }

    /**
     * Validate reset token.
     */
    private function validateResetToken(string $email, string $token): bool
    {
        $key = self::CACHE_PREFIX.hash('sha256', $email);
        $data = Cache::get($key);

        if (! $data || ! is_array($data)) {
            return false;
        }

        // Check if token matches
        if (($data['token'] ?? '') !== $token) {
            // Increment attempts
            $attempts = is_numeric($data['attempts'] ?? null) ? (int) ($data['attempts'] ?? 0) : 0;
            $data['attempts'] = $attempts + 1;
            Cache::put($key, $data, now()->addMinutes(self::TOKEN_EXPIRY));

            // Block if too many attempts
            if ($data['attempts'] >= self::MAX_ATTEMPTS) {
                Cache::forget($key);
            }

            return false;
        }

        // Check if token is expired
        $createdAtValue = $data['created_at'] ?? null;
        if ($createdAtValue && is_string($createdAtValue)) {
            $createdAt = Carbon::parse($createdAtValue);
            if ($createdAt->addMinutes(self::TOKEN_EXPIRY)->isPast()) {
                Cache::forget($key);

                return false;
            }
        }

        return true;
    }

    /**
     * Clear reset token.
     */
    private function clearResetToken(string $email): void
    {
        $key = self::CACHE_PREFIX.hash('sha256', $email);
        Cache::forget($key);
    }
}
