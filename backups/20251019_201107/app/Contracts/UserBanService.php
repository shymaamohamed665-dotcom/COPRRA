<?php

declare(strict_types=1);

namespace App\Contracts;

use Carbon\Carbon;

interface UserBanService
{
    /**
     * Ban a user.
     */
    public function banUser(\App\Models\User $user, string $reason, ?string $description = null, ?Carbon $expiresAt = null): bool;

    /**
     * Unban a user.
     */
    public function unbanUser(\App\Models\User $user, ?string $reason = null): bool;

    /**
     * Check if user is banned.
     */
    public function isUserBanned(\App\Models\User $user): bool;

    /**
     * Get ban information.
     *
     * @return array<string, bool|string|null>|null
     */
    public function getBanInfo(\App\Models\User $user): ?array;

    /**
     * Get all banned users.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    public function getBannedUsers(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Get users with expired bans.
     *
     * @return \Illuminate\Database\Eloquent\Collection<int, \App\Models\User>
     */
    public function getUsersWithExpiredBans(): \Illuminate\Database\Eloquent\Collection;

    /**
     * Clean up expired bans.
     */
    public function cleanupExpiredBans(): int;

    /**
     * Get ban statistics.
     *
     * @return array<string, int|array<string, string>>
     */
    public function getBanStatistics(): array;

    /**
     * Get ban reasons.
     *
     * @return list<string>
     */
    public function getBanReasons(): array;

    /**
     * Check if user can be banned.
     */
    public function canBanUser(\App\Models\User $user): bool;

    /**
     * Check if user can be unbanned.
     */
    public function canUnbanUser(\App\Models\User $user): bool;

    /**
     * Get ban history for user.
     *
     * @return list<array<string, bool|string|null>>
     */
    public function getBanHistory(\App\Models\User $user): array;

    /**
     * Extend ban duration.
     */
    public function extendBan(\App\Models\User $user, Carbon $newExpiresAt, ?string $reason = null): bool;

    /**
     * Reduce ban duration.
     */
    public function reduceBan(\App\Models\User $user, Carbon $newExpiresAt, ?string $reason = null): bool;
}
