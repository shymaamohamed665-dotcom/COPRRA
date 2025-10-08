<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasEnumUtilities;

enum OrderStatus: string
{
    use HasEnumUtilities;

    case PENDING = 'pending';
    case PROCESSING = 'processing';
    case SHIPPED = 'shipped';
    case DELIVERED = 'delivered';
    case CANCELLED = 'cancelled';
    case REFUNDED = 'refunded';

    /**
     * Get the display name for the status.
     */
    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'قيد الانتظار',
            self::PROCESSING => 'قيد المعالجة',
            self::SHIPPED => 'تم الشحن',
            self::DELIVERED => 'تم التسليم',
            self::CANCELLED => 'ملغي',
            self::REFUNDED => 'مسترد',
        };
    }

    /**
     * Get the color for the status (for UI).
     */
    public function color(): string
    {
        return match ($this) {
            self::PENDING => 'yellow',
            self::PROCESSING => 'blue',
            self::SHIPPED => 'purple',
            self::DELIVERED => 'green',
            self::CANCELLED => 'red',
            self::REFUNDED => 'orange',
        };
    }

    /**
     * Get allowed status transitions.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::PENDING => [self::PROCESSING, self::CANCELLED],
            self::PROCESSING => [self::SHIPPED, self::CANCELLED],
            self::DELIVERED => [],
            self::CANCELLED => [],
            self::REFUNDED => [],
            self::SHIPPED => [self::DELIVERED],
        };
    }

    /**
     * Check if status can transition to target status.
     */
    public function canTransitionTo(self $targetStatus): bool
    {
        return in_array($targetStatus, $this->allowedTransitions(), true);
    }

    /**
     * Get permissions for the status (not applicable for order statuses).
     *
     * @return array<string, string>
     */
    public function permissions(): array
    {
        return [];
    }

    /**
     * Check if status has a specific permission (not applicable for order statuses).
     */
    public function hasPermission(string $permission): bool
    {
        return false;
    }

    /**
     * Check if status is admin (not applicable for order statuses).
     */
    public function isAdmin(): bool
    {
        return false;
    }
}
