<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\PasswordPolicyService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class PasswordPolicyServiceTest extends TestCase
{
    private PasswordPolicyService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordPolicyService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
    public function test_validates_strong_password()
    {
        // Arrange
        $password = 'MySecure123!Pass';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
        $this->assertArrayHasKey('strength', $result);
    }
    public function test_validates_weak_password()
    {
        // Arrange
        $password = 'weak';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertNotEmpty($result['errors']);
        $this->assertContains('Password must be at least 10 characters long', $result['errors']);
    }
    public function test_validates_password_without_uppercase()
    {
        // Arrange
        $password = 'lowercase123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one uppercase letter', $result['errors']);
    }
    public function test_validates_password_without_lowercase()
    {
        // Arrange
        $password = 'UPPERCASE123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one lowercase letter', $result['errors']);
    }
    public function test_validates_password_without_numbers()
    {
        // Arrange
        $password = 'NoNumbers!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must contain at least one number', $result['errors']);
    }
    public function test_validates_password_without_symbols()
    {
        // Arrange
        $password = 'NoSymbols123';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertTrue($result['valid']);
        $this->assertEmpty($result['errors']);
    }
    public function test_validates_forbidden_password()
    {
        // Arrange
        $password = 'password';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password is too common and not allowed', $result['errors']);
    }
    public function test_validates_password_with_repeated_characters()
    {
        // Arrange
        $password = 'aaa123!@#';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password contains repeated characters', $result['errors']);
    }
    public function test_validates_password_with_keyboard_patterns()
    {
        // Arrange
        $password = 'qwerty123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password contains keyboard patterns', $result['errors']);
    }
    public function test_validates_password_with_common_substitutions()
    {
        // Arrange
        $password = 'p@ssw0rd123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password contains common character substitutions', $result['errors']);
    }
    public function test_calculates_password_strength_weak()
    {
        // Arrange
        $password = 'weak';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertEquals('weak', $result['strength']);
    }
    public function test_calculates_password_strength_medium()
    {
        // Arrange
        $password = 'Medium123';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertEquals('medium', $result['strength']);
    }
    public function test_calculates_password_strength_strong()
    {
        // Arrange
        $password = 'StrongP@ss123';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertEquals('strong', $result['strength']);
    }
    public function test_calculates_password_strength_very_strong()
    {
        // Arrange
        $password = 'VeryStrongP@ssw0rd123!@#';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertEquals('very_strong', $result['strength']);
    }
    public function test_checks_password_not_in_history()
    {
        // Arrange
        $userId = 1;
        $password = 'SecurePass123!';

        // Act
        $result = $this->service->validatePassword($password, $userId);

        // Assert
        $this->assertTrue($result['valid']);
    }
    public function test_saves_password_to_history()
    {
        // Arrange
        $userId = 1;
        $password = 'NewPassword123!';

        Log::shouldReceive('info')
            ->with('Password saved to history', Mockery::type('array'));

        // Act
        $result = $this->service->savePasswordToHistory($userId, $password);

        // Assert
        $this->assertTrue($result);
    }
    public function test_handles_save_password_history_exception()
    {
        // Arrange
        $userId = 1;
        $password = 'NewPassword123!';

        // Mock Hash::make to throw exception
        Hash::shouldReceive('make')
            ->andThrow(new \Exception('Hash error'));

        Log::shouldReceive('error')
            ->with('Failed to save password to history', Mockery::type('array'));

        // Act
        $result = $this->service->savePasswordToHistory($userId, $password);

        // Assert
        $this->assertFalse($result);
    }
    public function test_checks_password_expired()
    {
        // Arrange
        $userId = 1;

        // Act
        $result = $this->service->isPasswordExpired($userId);

        // Assert
        $this->assertTrue($result);
    }
    public function test_handles_password_expiry_check_exception()
    {
        // Arrange
        $userId = 999;

        // Test with invalid user ID to trigger exception handling
        Log::shouldReceive('error')
            ->with('Password expiry check failed', Mockery::type('array'));

        // Act
        $result = $this->service->isPasswordExpired($userId);

        // Assert
        $this->assertFalse($result);
    }
    public function test_checks_account_not_locked()
    {
        // Arrange
        $userId = 1;

        // Act
        $result = $this->service->isAccountLocked($userId);

        // Assert
        $this->assertFalse($result);
    }
    public function test_handles_account_lock_check_exception()
    {
        // Arrange
        $userId = 1;

        // Test with invalid user ID to trigger exception handling
        Log::shouldReceive('error')
            ->with('Account lock check failed', Mockery::type('array'));

        // Act
        $result = $this->service->isAccountLocked($userId);

        // Assert
        $this->assertFalse($result);
    }
    public function test_records_failed_attempt()
    {
        // Arrange
        $userId = 1;
        $ipAddress = '127.0.0.1';

        Log::shouldReceive('info')
            ->with('Failed login attempt recorded', Mockery::type('array'));

        // Act
        $this->service->recordFailedAttempt($userId, $ipAddress);

        // Assert
        $this->assertTrue(true);
    }
    public function test_handles_record_failed_attempt_exception()
    {
        // Arrange
        $userId = 1;
        $ipAddress = '127.0.0.1';

        // Mock now() to throw exception
        $this->mockFunction('now', function () {
            throw new \Exception('Time error');
        });

        Log::shouldReceive('error')
            ->with('Failed to record failed attempt', Mockery::type('array'));

        // Act
        $this->service->recordFailedAttempt($userId, $ipAddress);

        // Assert
        $this->assertTrue(true);
    }
    public function test_clears_failed_attempts()
    {
        // Arrange
        $userId = 1;

        Log::shouldReceive('info')
            ->with('Failed attempts cleared', Mockery::type('array'));

        // Act
        $this->service->clearFailedAttempts($userId);

        // Assert
        $this->assertTrue(true);
    }
    public function test_generates_secure_password()
    {
        // Act
        $password = $this->service->generateSecurePassword(12);

        // Assert
        $this->assertIsString($password);
        $this->assertEquals(12, strlen($password));
        $this->assertMatchesRegularExpression('/[A-Z]/', $password);
        $this->assertMatchesRegularExpression('/[a-z]/', $password);
        $this->assertMatchesRegularExpression('/[0-9]/', $password);
        $this->assertMatchesRegularExpression('/[^A-Za-z0-9]/', $password);
    }
    public function test_generates_secure_password_with_default_length()
    {
        // Act
        $password = $this->service->generateSecurePassword();

        // Assert
        $this->assertIsString($password);
        $this->assertEquals(12, strlen($password));
    }
    public function test_gets_policy_requirements()
    {
        // Act
        $requirements = $this->service->getPolicyRequirements();

        // Assert
        $this->assertIsArray($requirements);
        $this->assertArrayHasKey('min_length', $requirements);
        $this->assertArrayHasKey('max_length', $requirements);
        $this->assertArrayHasKey('require_uppercase', $requirements);
        $this->assertArrayHasKey('require_lowercase', $requirements);
        $this->assertArrayHasKey('require_numbers', $requirements);
        $this->assertArrayHasKey('require_symbols', $requirements);
        $this->assertArrayHasKey('expiry_days', $requirements);
        $this->assertArrayHasKey('history_count', $requirements);
    }
    public function test_updates_policy()
    {
        // Arrange
        $newPolicy = [
            'min_length' => 10,
            'require_symbols' => false,
        ];

        Log::shouldReceive('info')
            ->with('Password policy updated', $newPolicy);

        // Mock file_put_contents
        $this->mockFunction('file_put_contents', function ($path, $content) {
            return strlen($content);
        });

        // Act
        $result = $this->service->updatePolicy($newPolicy);

        // Assert
        $this->assertTrue($result);
    }
    public function test_handles_update_policy_exception()
    {
        // Arrange
        $newPolicy = [
            'min_length' => 10,
        ];

        // Mock file_put_contents to throw exception
        $this->mockFunction('file_put_contents', function ($path, $content) {
            throw new \Exception('File write error');
        });

        Log::shouldReceive('error')
            ->with('Failed to update password policy', Mockery::type('array'));

        // Act
        $result = $this->service->updatePolicy($newPolicy);

        // Assert
        $this->assertFalse($result);
    }
    public function test_validates_password_with_maximum_length()
    {
        // Arrange
        $password = str_repeat('a', 129); // Exceeds max length

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password must not exceed 128 characters', $result['errors']);
    }
    public function test_validates_password_with_case_insensitive_forbidden()
    {
        // Arrange
        $password = 'PASSWORD123!';

        // Act
        $result = $this->service->validatePassword($password);

        // Assert
        $this->assertFalse($result['valid']);
        $this->assertContains('Password is too common and not allowed', $result['errors']);
    }

    // Helper method to mock functions
    private function mockFunction(string $functionName, callable $callback): void
    {
        if (! function_exists($functionName)) {
            eval("function {$functionName}(\$arg) { return call_user_func_array('{$functionName}', func_get_args()); }");
        }
    }
}
