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

    public function __construct(
        private readonly AuthManager $auth,
        private readonly LoggerInterface $logger,
        private readonly User $user
    ) {}

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
     * @return array<string, bool|string|null>|null
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

    private function isValidBanReason(string $reason): bool
    {
        return $reason !== '' && array_key_exists($reason, self::BAN_REASONS);
    }
}
