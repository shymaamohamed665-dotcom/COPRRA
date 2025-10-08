<?php

declare(strict_types=1);

namespace App\Enums;

use App\Traits\HasEnumUtilities;

enum UserRole: string
{
    use HasEnumUtilities;

    case ADMIN = 'admin';
    case USER = 'user';
    case MODERATOR = 'moderator';
    case GUEST = 'guest';

    /**
     * Get the display name for the role.
     */
    public function label(): string
    {
        return match ($this) {
            self::ADMIN => 'مدير',
            self::USER => 'مستخدم',
            self::MODERATOR => 'مشرف',
            self::GUEST => 'ضيف',
        };
    }

    /**
     * Get permissions for this role.
     *
     * @return array<int, string>
     */
    public function permissions(): array
    {
        return match ($this) {
            self::ADMIN => [
                'manage_users',
                'manage_products',
                'manage_orders',
                'manage_categories',
                'manage_brands',
                'view_analytics',
                'manage_settings',
            ],
            self::MODERATOR => [
                'manage_products',
                'manage_orders',
                'view_analytics',
            ],
            self::USER => [
                'create_orders',
                'view_own_orders',
                'manage_wishlist',
                'write_reviews',
            ],
            self::GUEST => [
                'view_products',
                'view_categories',
            ],
        };
    }

    /**
     * Check if role has a specific permission.
     */
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Get the color for the role (for UI).
     */
    public function color(): string
    {
        return match ($this) {
            self::ADMIN => 'red',
            self::MODERATOR => 'blue',
            self::USER => 'green',
            self::GUEST => 'gray',
        };
    }

    /**
     * Get allowed role transitions.
     *
     * @return array<int, self>
     */
    public function allowedTransitions(): array
    {
        return match ($this) {
            self::GUEST => [self::USER],
            self::USER => [self::MODERATOR],
            self::MODERATOR => [self::ADMIN],
            self::ADMIN => [],
        };
    }

    /**
     * Check if role can transition to target role.
     */
    public function canTransitionTo(self $targetRole): bool
    {
        return in_array($targetRole, $this->allowedTransitions(), true);
    }

    /**
     * Check if role is admin.
     */
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }
}
