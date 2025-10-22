<?php

declare(strict_types=1);

// phpcs:disable PHPCompatibility.Variables.ForbiddenThisUseContexts.OutsideObjectContext, PHPCompatibility.FunctionDeclarations.NewParamTypeDeclarations.SelfOutsideClassScopeFound

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
    #[\Override]
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
     * @return array<string>
     *
     * @psalm-return list{0: string, 1: string, 2?: 'manage_orders'|'manage_wishlist'|'view_analytics', 3?: 'manage_categories'|'write_reviews', 4?: 'manage_brands', 5?: 'view_analytics', 6?: 'manage_settings'}
     */
    #[\Override]
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
    #[\Override]
    public function hasPermission(string $permission): bool
    {
        return in_array($permission, $this->permissions(), true);
    }

    /**
     * Get the color for the role (for UI).
     */
    #[\Override]
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
     * @return array<App\Enums\UserRole::ADMIN|App\Enums\UserRole::MODERATOR|App\Enums\UserRole::USER>
     *
     * @psalm-return list{0?: App\Enums\UserRole::ADMIN|App\Enums\UserRole::MODERATOR|App\Enums\UserRole::USER}
     */
    #[\Override]
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
    #[\Override]
    public function canTransitionTo(self $targetRole): bool
    {
        return in_array($targetRole, $this->allowedTransitions(), true);
    }

    /**
     * Check if role is admin.
     */
    #[\Override]
    public function isAdmin(): bool
    {
        return $this === self::ADMIN;
    }

    /**
     * Determine if the role has moderator-level privileges.
     */
    public function isModerator(): bool
    {
        return $this === self::ADMIN || $this === self::MODERATOR;
    }

    /**
     * Get associative array of value => label pairs for options.
     *
     * @return array<string>
     *
     * @psalm-return array{admin: string, user: string, moderator: string, guest: string}
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
