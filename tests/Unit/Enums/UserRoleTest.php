<?php

declare(strict_types=1);

namespace Tests\Unit\Enums;

use App\Enums\UserRole;
use PHPUnit\Framework\TestCase;

class UserRoleTest extends TestCase
{
    public function test_enum_has_all_expected_cases(): void
    {
        $cases = UserRole::cases();

        $this->assertCount(4, $cases);
        $this->assertContains(UserRole::ADMIN, $cases);
        $this->assertContains(UserRole::USER, $cases);
        $this->assertContains(UserRole::MODERATOR, $cases);
        $this->assertContains(UserRole::GUEST, $cases);
    }

    public function test_enum_values_are_correct(): void
    {
        $this->assertEquals('admin', UserRole::ADMIN->value);
        $this->assertEquals('user', UserRole::USER->value);
        $this->assertEquals('moderator', UserRole::MODERATOR->value);
        $this->assertEquals('guest', UserRole::GUEST->value);
    }

    public function test_label_returns_correct_arabic_text(): void
    {
        $this->assertEquals('مدير', UserRole::ADMIN->label());
        $this->assertEquals('مستخدم', UserRole::USER->label());
        $this->assertEquals('مشرف', UserRole::MODERATOR->label());
        $this->assertEquals('ضيف', UserRole::GUEST->label());
    }

    public function test_admin_has_all_permissions(): void
    {
        $permissions = UserRole::ADMIN->permissions();

        $this->assertContains('manage_users', $permissions);
        $this->assertContains('manage_products', $permissions);
        $this->assertContains('manage_orders', $permissions);
        $this->assertContains('manage_categories', $permissions);
        $this->assertContains('manage_brands', $permissions);
        $this->assertContains('view_analytics', $permissions);
        $this->assertContains('manage_settings', $permissions);
    }

    public function test_moderator_has_limited_permissions(): void
    {
        $permissions = UserRole::MODERATOR->permissions();

        $this->assertContains('manage_products', $permissions);
        $this->assertContains('manage_orders', $permissions);
        $this->assertContains('view_analytics', $permissions);
        $this->assertNotContains('manage_users', $permissions);
        $this->assertNotContains('manage_settings', $permissions);
    }

    public function test_user_has_basic_permissions(): void
    {
        $permissions = UserRole::USER->permissions();

        $this->assertContains('create_orders', $permissions);
        $this->assertContains('view_own_orders', $permissions);
        $this->assertContains('manage_wishlist', $permissions);
        $this->assertContains('write_reviews', $permissions);
        $this->assertNotContains('manage_products', $permissions);
    }

    public function test_guest_has_minimal_permissions(): void
    {
        $permissions = UserRole::GUEST->permissions();

        $this->assertContains('view_products', $permissions);
        $this->assertContains('view_categories', $permissions);
        $this->assertNotContains('create_orders', $permissions);
        $this->assertNotContains('manage_wishlist', $permissions);
    }

    public function test_has_permission_returns_true_for_valid_permission(): void
    {
        $this->assertTrue(UserRole::ADMIN->hasPermission('manage_users'));
        $this->assertTrue(UserRole::MODERATOR->hasPermission('manage_products'));
        $this->assertTrue(UserRole::USER->hasPermission('create_orders'));
        $this->assertTrue(UserRole::GUEST->hasPermission('view_products'));
    }

    public function test_has_permission_returns_false_for_invalid_permission(): void
    {
        $this->assertFalse(UserRole::USER->hasPermission('manage_users'));
        $this->assertFalse(UserRole::GUEST->hasPermission('create_orders'));
        $this->assertFalse(UserRole::MODERATOR->hasPermission('manage_settings'));
    }

    public function test_is_admin_returns_true_only_for_admin(): void
    {
        $this->assertTrue(UserRole::ADMIN->isAdmin());
        $this->assertFalse(UserRole::MODERATOR->isAdmin());
        $this->assertFalse(UserRole::USER->isAdmin());
        $this->assertFalse(UserRole::GUEST->isAdmin());
    }

    public function test_is_moderator_returns_true_for_admin_and_moderator(): void
    {
        $this->assertTrue(UserRole::ADMIN->isModerator());
        $this->assertTrue(UserRole::MODERATOR->isModerator());
        $this->assertFalse(UserRole::USER->isModerator());
        $this->assertFalse(UserRole::GUEST->isModerator());
    }

    public function test_to_array_returns_correct_format(): void
    {
        $array = UserRole::toArray();

        $this->assertIsArray($array);
        $this->assertArrayHasKey('ADMIN', $array);
        $this->assertEquals('admin', $array['ADMIN']);
        $this->assertArrayHasKey('USER', $array);
        $this->assertEquals('user', $array['USER']);
    }

    public function test_options_returns_value_label_pairs(): void
    {
        $options = UserRole::options();

        $this->assertIsArray($options);
        $this->assertArrayHasKey('admin', $options);
        $this->assertEquals('مدير', $options['admin']);
        $this->assertArrayHasKey('user', $options);
        $this->assertEquals('مستخدم', $options['user']);
    }

    public function test_can_create_from_string(): void
    {
        $role = UserRole::from('admin');
        $this->assertEquals(UserRole::ADMIN, $role);

        $role = UserRole::from('user');
        $this->assertEquals(UserRole::USER, $role);
    }

    public function test_try_from_returns_null_for_invalid_value(): void
    {
        $role = UserRole::tryFrom('invalid');
        $this->assertNull($role);
    }
}
