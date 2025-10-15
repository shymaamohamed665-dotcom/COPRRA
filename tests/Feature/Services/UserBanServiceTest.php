<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\UserBanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class UserBanServiceTest extends TestCase
{
    use RefreshDatabase;

    private UserBanService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new UserBanService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    private function createUser(int $id = 1, bool $isBlocked = false): \App\Models\User
    {
        $user = new \App\Models\User;
        $user->id = $id;
        $user->name = 'Test User';
        $user->email = 'test@example.com';
        $user->password = 'password';
        $user->is_blocked = $isBlocked;
        $user->ban_reason = null;
        $user->ban_description = null;
        $user->ban_expires_at = null;

        return $user;
    }

    public function test_can_be_instantiated()
    {
        // Act & Assert
        $this->assertInstanceOf(UserBanService::class, $this->service);
    }

    public function test_checks_user_is_not_banned_by_default()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->isUserBanned($user);

        // Assert
        $this->assertFalse($result);
    }

    public function test_handles_ban_user_with_valid_reason()
    {
        // Arrange
        $user = $this->createUser();
        $reason = 'violation';
        $duration = 7; // days

        // Act
        $result = $this->service->banUser($user, $reason, null, now()->addDays($duration));

        // Assert
        $this->assertTrue($result);
    }

    public function test_handles_ban_user_with_invalid_reason()
    {
        // Arrange
        $user = $this->createUser();
        $reason = ''; // Empty reason
        $duration = 7;

        // Act
        $result = $this->service->banUser($user, $reason, null, now()->addDays($duration));

        // Assert
        $this->assertFalse($result);
    }

    public function test_handles_permanent_ban()
    {
        // Arrange
        $user = $this->createUser();
        $reason = 'security';
        $duration = null; // Permanent ban

        // Act
        $result = $this->service->banUser($user, $reason, null, now()->addDays($duration));

        // Assert
        $this->assertTrue($result);
    }

    public function test_handles_unban_user()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->unbanUser($user);

        // Assert
        $this->assertTrue($result);
    }

    public function test_gets_ban_info_for_user()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->getBanInfo($user);

        // Assert
        $this->assertNull($result); // No ban info by default
    }

    public function test_handles_auto_unban_expired_user()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->cleanupExpiredBans();

        // Assert
        $this->assertIsInt($result);
    }

    public function test_gets_banned_users()
    {
        // Act
        $result = $this->service->getBannedUsers();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_gets_users_with_expired_bans()
    {
        // Act
        $result = $this->service->getUsersWithExpiredBans();

        // Assert
        $this->assertInstanceOf(\Illuminate\Database\Eloquent\Collection::class, $result);
    }

    public function test_cleans_up_expired_bans()
    {
        // Act
        $result = $this->service->cleanupExpiredBans();

        // Assert
        $this->assertIsInt($result);
        $this->assertGreaterThanOrEqual(0, $result);
    }

    public function test_gets_ban_statistics()
    {
        // Act
        $result = $this->service->getBanStatistics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_banned', $result);
        $this->assertArrayHasKey('permanent_bans', $result);
        $this->assertArrayHasKey('temporary_bans', $result);
        $this->assertArrayHasKey('expired_bans', $result);
    }

    public function test_gets_ban_reasons()
    {
        // Act
        $result = $this->service->getBanReasons();

        // Assert
        $this->assertIsArray($result);
    }

    public function test_checks_can_ban_user()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->canBanUser($user);

        // Assert
        $this->assertTrue($result);
    }

    public function test_checks_can_unban_user()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->canUnbanUser($user);

        // Assert
        $this->assertFalse($result);
    }

    public function test_gets_ban_history()
    {
        // Arrange
        $user = $this->createUser();

        // Act
        $result = $this->service->getBanHistory($user);

        // Assert
        $this->assertIsArray($result);
    }

    public function test_handles_extend_ban()
    {
        // Arrange
        $user = $this->createUser();
        $additionalDays = now()->addDays(7);

        // Act
        $result = $this->service->extendBan($user, $additionalDays);

        // Assert
        $this->assertFalse($result);
    }

    public function test_handles_reduce_ban()
    {
        // Arrange
        $user = $this->createUser();
        $reduceDays = now()->addDays(3);

        // Act
        $result = $this->service->reduceBan($user, $reduceDays);

        // Assert
        $this->assertFalse($result);
    }
}
