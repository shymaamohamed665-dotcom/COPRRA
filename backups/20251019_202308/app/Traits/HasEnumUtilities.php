<?php

declare(strict_types=1);

namespace App\Traits;

trait HasEnumUtilities
{
    /**
     * Get all statuses as array.
     *
     * @return array<string>
     *
     * @psalm-return array<string, string>
     */
    public static function toArray(): array
    {
        return array_column(self::cases(), 'value', 'name');
    }

    /**
     * Get the display name for the status.
     */
    abstract public function label(): string;

    /**
     * Get the color for the status (for UI).
     */
    abstract public function color(): string;

    /**
     * Get allowed transitions from this status.
     *
     * @return array<string, string>
     */
    abstract public function allowedTransitions(): array;

    /** @return array */

    /**
     * Check if transition to another status is allowed.
     */
    abstract public function canTransitionTo(self $newStatus): bool;

    /**
     * Get permissions for the role.
     *
     * @return array<string, string>
     */
    abstract public function permissions(): array;

    /** @return array */

    /**
     * Check if role has a specific permission.
     */
    abstract public function hasPermission(string $permission): bool;

    /**
     * Check if role is admin.
     */
    abstract public function isAdmin(): bool;
}
