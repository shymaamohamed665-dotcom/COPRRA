<?php

declare(strict_types=1);

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
     * @return array<self>
     */
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
    public function canTransitionTo(self $status): bool
    {
        return in_array($status, $this->allowedTransitions(), true);
    }

    /**
     * Get permissions for this status (not applicable for notification statuses).
     *
     * @return array<string>
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Check if this status has a specific permission (not applicable for notification statuses).
     */
    public function hasPermission(string $permission): bool
    {
        return false;
    }

    /**
     * Check if this status represents admin privileges (not applicable for notification statuses).
     */
    public function isAdmin(): bool
    {
        return false;
    }
}
