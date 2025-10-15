<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Auth\AuthManager;
use Illuminate\Database\Eloquent\Collection;
use Psr\Log\LoggerInterface;

final class UserBanService
{
    private const BAN_REASONS = [
        'spam' => 'إرسال رسائل مزعجة',
        'abuse' => 'إساءة استخدام',
        'fraud' => 'احتيال',
        'violation' => 'انتهاك شروط الاستخدام',
        'security' => 'مخاطر أمنية',
        'other' => 'أسباب أخرى',
    ];

    private AuthManager $auth;

    private LoggerInterface $logger;

    private User $user;

    public function __construct(
        ?AuthManager $auth = null,
        ?LoggerInterface $logger = null,
        ?User $user = null
    ) {
        // Allow no-arg construction in tests
        $this->auth = $auth ?? app('auth');
        $this->logger = $logger ?? app('log');
        $this->user = $user ?? new User;
    }

    /**
     * Check if a user is currently banned.
     */
    public function isUserBanned(User $user): bool
    {
        return $user->isBanned();
    }

    /**
     * Ban a user.
     */
    public function banUser(
        User $user,
        string $reason,
        ?string $description = null,
        ?Carbon $expiresAt = null
    ): bool {
        if (! $this->isValidBanReason($reason)) {
            return false;
        }

        $user->is_blocked = true;
        $user->ban_reason = $reason;
        $user->ban_description = $description;
        $user->banned_at = now();
        $user->ban_expires_at = $expiresAt;
        $user->save();

        // Log ban action
        $this->logger->info('User banned', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
            'description' => $description,
            'expires_at' => $expiresAt?->toISOString(),
            'banned_by' => $this->auth->id(),
        ]);

        return true;
    }

    /**
     * Unban a user.
     *
     * @return true
     */
    public function unbanUser(User $user, ?string $reason = null): bool
    {
        $user->is_blocked = false;
        $user->ban_reason = null;
        $user->ban_description = null;
        $user->banned_at = null;
        $user->ban_expires_at = null;
        $user->save();

        // Log unban action
        $this->logger->info('User unbanned', [
            'user_id' => $user->id,
            'email' => $user->email,
            'reason' => $reason,
            'unbanned_by' => $this->auth->id(),
        ]);

        return true;
    }

    /**
     * Get ban information.
     *
     * @return (bool|null|string)[]|null
     *
     * @psalm-return array{is_banned: bool, reason: null|string, description: null|string, banned_at: null|string, expires_at: null|string, is_permanent: bool, reason_text: string}|null
     */
    public function getBanInfo(User $user): ?array
    {
        if (! $user->isBanned()) {
            return null;
        }

        return [
            'is_banned' => $user->isBanned(),
            'reason' => $user->ban_reason,
            'description' => $user->ban_description,
            'banned_at' => $user->banned_at ? (new Carbon($user->banned_at))->toISOString() : null,
            'expires_at' => $user->ban_expires_at ?
                (new Carbon($user->ban_expires_at))->toISOString() : null,
            'is_permanent' => $user->ban_expires_at === null,
            'reason_text' => self::BAN_REASONS[$user->ban_reason] ?? 'غير محدد',
        ];
    }

    /**
     * Get all banned users.
     *
     * @return Collection<int, User>
     */
    public function getBannedUsers(): Collection
    {
        return $this->user->where('is_blocked', true)
            ->where(static function ($query): void {
                $query->whereNull('ban_expires_at')
                    ->orWhere('ban_expires_at', '>', now());
            })
            ->get();
    }

    /**
     * Get users with expired bans.
     *
     * @return Collection<int, User>
     */
    public function getUsersWithExpiredBans(): Collection
    {
        return $this->user->where('is_blocked', true)
            ->where('ban_expires_at', '<=', now())
            ->get();
    }

    /**
     * Clean up expired bans by unbanning users whose ban has expired.
     *
     * @psalm-return int<0, max>
     */
    public function cleanupExpiredBans(): int
    {
        $expired = $this->getUsersWithExpiredBans();
        $count = 0;

        foreach ($expired as $user) {
            if ($this->unbanUser($user, 'expired')) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * Get ban statistics.
     *
     * @return array<string, int|array<string, string>>
     */
    public function getBanStatistics(): array
    {
        $bannedUsersQuery = $this->user->where('is_blocked', true);

        $totalBanned = $bannedUsersQuery->count();
        $permanentBans = (clone $bannedUsersQuery)->whereNull('ban_expires_at')->count();
        $temporaryBans = (clone $bannedUsersQuery)->whereNotNull('ban_expires_at')->count();
        $expiredBans = (clone $bannedUsersQuery)->where('ban_expires_at', '<=', now())->count();

        return [
            'total_banned' => $totalBanned,
            'permanent_bans' => $permanentBans,
            'temporary_bans' => $temporaryBans,
            'expired_bans' => $expiredBans,
            'ban_reasons' => self::BAN_REASONS,
        ];
    }

    /**
     * Get available ban reasons (keys).
     *
     * @return list<string>
     */
    public function getBanReasons(): array
    {
        return array_keys(self::BAN_REASONS);
    }

    private function isValidBanReason(string $reason): bool
    {
        return $reason !== '' && array_key_exists($reason, self::BAN_REASONS);
    }

    /**
     * Determine if a user can be banned.
     */
    public function canBanUser(User $user): bool
    {
        return ! (bool) ($user->is_blocked ?? false);
    }

    /**
     * Determine if a user can be unbanned.
     */
    public function canUnbanUser(User $user): bool
    {
        return (bool) ($user->is_blocked ?? false);
    }

    /**
     * Get user's ban history.
     *
     * @psalm-return array<never, never>
     */
    public function getBanHistory(User $user): array
    {
        // Placeholder: in real app, fetch from audit/logs table
        return [];
    }

    /**
     * Extend user's ban duration.
     *
     * @return false
     */
    public function extendBan(User $user, Carbon $newExpiry): bool
    {
        if ($user->ban_expires_at === null) {
            return false;
        }

        // In real app, you would update expiry and persist
        // $user->ban_expires_at = $newExpiry;
        // $user->save();

        return false;
    }

    /**
     * Reduce user's ban duration.
     *
     * @return false
     */
    public function reduceBan(User $user, Carbon $newExpiry): bool
    {
        if ($user->ban_expires_at === null) {
            return false;
        }

        // In real app, you would update expiry and persist
        // $user->ban_expires_at = $newExpiry;
        // $user->save();

        return false;
    }
}
