<?php

declare(strict_types=1);

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext, PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.SelfOutsideClassScopeFound

namespace App\Enums;

use App\Traits\HasEnumUtilities;

enum NotificationStatus: string
{
    use HasEnumUtilities;

    case PENDING = 'pending';
    case SENT = 'sent';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';

    /**
     * Get the display name for the status.
     */
    #[\Override]
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::SENT => 'تم الإرسال',
            self::FAILED => 'فشل',
            self::CANCELLED => 'ملغي',
        };
    }

    /**
     * Get the color for the status (for UI).
     */
    #[\Override]
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::SENT => 'green',
            self::FAILED => 'red',
            self::CANCELLED => 'gray',
        };
    }

    /**
     * Get allowed transitions for this status.
     *
     * @return array<App\Enums\NotificationStatus::CANCELLED|App\Enums\NotificationStatus::FAILED|App\Enums\NotificationStatus::PENDING|App\Enums\NotificationStatus::SENT>
     *
     * @psalm-return list{0?: App\Enums\NotificationStatus::PENDING|App\Enums\NotificationStatus::SENT, 1?: App\Enums\NotificationStatus::FAILED, 2?: App\Enums\NotificationStatus::CANCELLED}
     */
    #[\Override]
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::SENT, self::FAILED, self::CANCELLED],
            self::SENT => [],
            self::FAILED => [self::PENDING],
            self::CANCELLED => [],
        };
    }

    /**
     * Check if this status can transition to another status.
     */
    #[\Override]
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get permissions for this status (not applicable for notification statuses).
     *
     * @psalm-return array<never, never>
     */
    #[\Override]
    public function permissions(): array
    {
        return [];
    }

    /**
     * Check if this status has a specific permission (not applicable for notification statuses).
     *
     * @SuppressWarnings("UnusedFormalParameter")
     */
    #[\Override]
    public function hasPermission(string $permission): bool
    {
        return false;
    }

    /**
     * Check if this status represents admin privileges (not applicable for notification statuses).
     */
    #[\Override]
    public function isAdmin(): bool
    {
        return false;
    }
}
// phpcs:enable
