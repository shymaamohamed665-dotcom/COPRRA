<?php

declare(strict_types=1);

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext, PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.SelfOutsideClassScopeFound

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
    #[\Override]
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
    #[\Override]
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
     * @return array<App\Enums\OrderStatus::CANCELLED|App\Enums\OrderStatus::DELIVERED|App\Enums\OrderStatus::PROCESSING|App\Enums\OrderStatus::SHIPPED>
     *
     * @psalm-return list{0?: App\Enums\OrderStatus::DELIVERED|App\Enums\OrderStatus::PROCESSING|App\Enums\OrderStatus::SHIPPED, 1?: App\Enums\OrderStatus::CANCELLED}
     */
    #[\Override]
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
    #[\Override]
    public function canTransitionTo(self $targetStatus): bool
    {
        return in_array($targetStatus, $this->allowedTransitions(), true);
    }

    /**
     * Get permissions for the status (not applicable for order statuses).
     *
     * @psalm-return array<never, never>
     */
    #[\Override]
    public function permissions(): array
    {
        return [];
    }

    /**
     * Check if status has a specific permission (not applicable for order statuses).
     *
     * @SuppressWarnings("UnusedFormalParameter")
     */
    #[\Override]
    public function hasPermission(string $permission): bool
    {
        return false;
    }

    /**
     * Check if status is admin (not applicable for order statuses).
     */
    #[\Override]
    public function isAdmin(): bool
    {
        return false;
    }

    /**
     * Get associative array of value => label pairs for options.
     *
     * @return array<string>
     *
     * @psalm-return array{pending: string, processing: string, shipped: string, delivered: string, cancelled: string, refunded: string}
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
// phpcs:enable
