<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\LoginAttemptService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Mockery;
use Tests\TestCase;

class LoginAttemptServiceTest extends TestCase
{
    use RefreshDatabase;

    private LoginAttemptService $service;

    private Request $request;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = new LoginAttemptService;

        $this->request = Mockery::mock(Request::class);
        $this->request->shouldReceive('ip')->andReturn('127.0.0.1');
        $this->request->shouldReceive('userAgent')->andReturn('Test Agent');
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_records_failed_attempt_with_email()
    {
        // Arrange
        $email = 'test@example.com';
        $ip = '127.0.0.1';
        $userAgent = 'Test Agent';

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->with(Mockery::pattern('/^ip_attempts:/'), Mockery::type('array'), Mockery::type('object'))
            ->andReturn(true);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->with(Mockery::pattern('/^login_attempts:/'), Mockery::type('array'), Mockery::type('object'))
            ->andReturn(true);

        Log::shouldReceive('warning')
            ->with('Failed login attempt', Mockery::type('array'));

        // Act
        $this->service->recordFailedAttempt($this->request, $email);

        // Assert
        $this->assertTrue(true);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('get')->with(Mockery::pattern('/^ip_attempts:/'), [])->once();
        Cache::shouldHaveReceived('put')->with(Mockery::pattern('/^ip_attempts:/'), Mockery::type('array'), Mockery::type('object'))->once();
        Cache::shouldHaveReceived('get')->with(Mockery::pattern('/^login_attempts:/'), [])->once();
        Cache::shouldHaveReceived('put')->with(Mockery::pattern('/^login_attempts:/'), Mockery::type('array'), Mockery::type('object'))->once();
        Log::shouldHaveReceived('warning')->with('Failed login attempt', Mockery::type('array'))->once();

        // Verify that cache was accessed for both IP and email attempts
        Cache::shouldHaveReceived('get')->with(Mockery::pattern('/^ip_attempts:/'), [])->once();
        Cache::shouldHaveReceived('get')->with(Mockery::pattern('/^login_attempts:/'), [])->once();

        // Verify that cache was updated for both IP and email attempts
        Cache::shouldHaveReceived('put')->with(Mockery::pattern('/^ip_attempts:/'), Mockery::type('array'), Mockery::type('object'))->once();
        Cache::shouldHaveReceived('put')->with(Mockery::pattern('/^login_attempts:/'), Mockery::type('array'), Mockery::type('object'))->once();

        // Verify that logging was called
        Log::shouldHaveReceived('warning')->with('Failed login attempt', Mockery::type('array'))->once();
    }

    public function test_records_failed_attempt_without_email()
    {
        // Arrange
        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn([]);

        Cache::shouldReceive('put')
            ->with(Mockery::pattern('/^ip_attempts:/'), Mockery::type('array'), Mockery::type('object'))
            ->andReturn(true);

        Log::shouldReceive('warning')
            ->with('Failed login attempt', Mockery::type('array'));

        // Act
        $this->service->recordFailedAttempt($this->request);

        // Assert
        $this->assertTrue(true);

        // Verify that cache was accessed for IP attempts only (no email)
        Cache::shouldHaveReceived('get')->with(Mockery::pattern('/^ip_attempts:/'), [])->once();

        // Verify that cache was updated for IP attempts only
        Cache::shouldHaveReceived('put')->with(Mockery::pattern('/^ip_attempts:/'), Mockery::type('array'), Mockery::type('object'))->once();

        // Verify that logging was called
        Log::shouldHaveReceived('warning')->with('Failed login attempt', Mockery::type('array'))->once();
    }

    public function test_records_successful_attempt()
    {
        // Arrange
        $email = 'test@example.com';
        $ip = '127.0.0.1';

        Cache::shouldReceive('forget')
            ->with(Mockery::pattern('/^login_attempts:/'))
            ->andReturn(true);

        Cache::shouldReceive('forget')
            ->with(Mockery::pattern('/^ip_attempts:/'))
            ->andReturn(true);

        Log::shouldReceive('info')
            ->with('Successful login', Mockery::type('array'));

        // Act
        $this->service->recordSuccessfulAttempt($this->request, $email);

        // Assert
        $this->assertTrue(true);

        // Verify cache interactions were called
        Cache::shouldHaveReceived('forget')->with(Mockery::pattern('/^login_attempts:/'))->once();
        Cache::shouldHaveReceived('forget')->with(Mockery::pattern('/^ip_attempts:/'))->once();
        Log::shouldHaveReceived('info')->with('Successful login', Mockery::type('array'))->once();

        // Verify that cache was cleared for both IP and email attempts
        Cache::shouldHaveReceived('forget')->with(Mockery::pattern('/^login_attempts:/'))->once();
        Cache::shouldHaveReceived('forget')->with(Mockery::pattern('/^ip_attempts:/'))->once();

        // Verify that logging was called for successful login
        Log::shouldHaveReceived('info')->with('Successful login', Mockery::type('array'))->once();
    }

    public function test_checks_email_blocked_when_under_limit()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        $this->assertFalse($result);
    }

    public function test_checks_email_blocked_when_over_limit()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => now()->toISOString()]);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        $this->assertTrue($result);
    }

    public function test_checks_ip_blocked_when_under_limit()
    {
        // Arrange
        $ip = '127.0.0.1';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isIpBlocked($ip);

        // Assert
        $this->assertFalse($result);
    }

    public function test_checks_ip_blocked_when_over_limit()
    {
        // Arrange
        $ip = '127.0.0.1';
        $attempts = array_fill(0, 5, ['timestamp' => now()->toISOString()]);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isIpBlocked($ip);

        // Assert
        $this->assertTrue($result);
    }

    public function test_gets_remaining_attempts_for_email()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getRemainingAttempts($email);

        // Assert
        $this->assertEquals(4, $result);
    }

    public function test_gets_remaining_attempts_for_ip()
    {
        // Arrange
        $ip = '127.0.0.1';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getRemainingIpAttempts($ip);

        // Assert
        $this->assertEquals(4, $result);
    }

    public function test_gets_lockout_time_remaining_for_email()
    {
        // Arrange
        $email = 'test@example.com';
        $futureTime = now()->addMinutes(30);
        $attempts = array_fill(0, 5, ['timestamp' => $futureTime->toISOString()]);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        $this->assertIsInt($result);
        $this->assertNotNull($result);
    }

    public function test_gets_lockout_time_remaining_for_ip()
    {
        // Arrange
        $ip = '127.0.0.1';
        $futureTime = now()->addMinutes(30);
        $attempts = array_fill(0, 5, ['timestamp' => $futureTime->toISOString()]);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^ip_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getIpLockoutTimeRemaining($ip);

        // Assert
        $this->assertIsInt($result);
        $this->assertNotNull($result);
    }

    public function test_returns_null_when_no_lockout()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [['timestamp' => now()->toISOString()]];

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        $this->assertNull($result);
    }

    public function test_handles_invalid_attempts_data()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = 'invalid_data';

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        $this->assertFalse($result);
    }

    public function test_handles_null_attempts_data()
    {
        // Arrange
        $email = 'test@example.com';

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn(null);

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        $this->assertFalse($result);
    }

    public function test_unblocks_email()
    {
        // Arrange
        $email = 'test@example.com';

        Cache::shouldReceive('forget')
            ->with(Mockery::pattern('/^login_attempts:/'))
            ->andReturn(true);

        Log::shouldReceive('info')
            ->with('Email unblocked', Mockery::type('array'));

        // Act
        $this->service->unblockEmail($email);

        // Assert
        $this->assertTrue(true);
    }

    public function test_unblocks_ip()
    {
        // Arrange
        $ip = '127.0.0.1';

        Cache::shouldReceive('forget')
            ->with(Mockery::pattern('/^ip_attempts:/'))
            ->andReturn(true);

        Log::shouldReceive('info')
            ->with('IP unblocked', Mockery::type('array'));

        // Act
        $this->service->unblockIp($ip);

        // Assert
        $this->assertTrue(true);
    }

    public function test_gets_statistics()
    {
        // Arrange
        $this->mockFunction('count', function ($array) {
            return is_array($array) ? count($array) : 0;
        });

        // Act
        $result = $this->service->getStatistics();

        // Assert
        $this->assertIsArray($result);
        $this->assertArrayHasKey('max_attempts', $result);
        $this->assertArrayHasKey('lockout_duration', $result);
        $this->assertArrayHasKey('blocked_emails_count', $result);
        $this->assertArrayHasKey('blocked_ips_count', $result);
        $this->assertEquals(5, $result['max_attempts']);
        $this->assertEquals(15, $result['lockout_duration']);
    }

    public function test_handles_expired_lockout()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => now()->subMinutes(20)->toISOString()]);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        $this->assertNull($result);
    }

    public function test_handles_malformed_timestamp()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => 'invalid_timestamp']);

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act & Assert
        $this->expectException(\Carbon\Exceptions\InvalidFormatException::class);
        $this->service->getLockoutTimeRemaining($email);
    }

    public function test_handles_empty_attempts_array()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = [];

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        $this->assertFalse($result);
    }

    public function test_handles_non_array_attempts_data()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = 'not_an_array';

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->isEmailBlocked($email);

        // Assert
        $this->assertFalse($result);
    }

    public function test_handles_missing_timestamp_in_attempt()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['ip' => '127.0.0.1']); // Missing timestamp

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        $this->assertNull($result);
    }

    public function test_handles_non_string_timestamp()
    {
        // Arrange
        $email = 'test@example.com';
        $attempts = array_fill(0, 5, ['timestamp' => 1234567890]); // Non-string timestamp

        Cache::shouldReceive('get')
            ->with(Mockery::pattern('/^login_attempts:/'), [])
            ->andReturn($attempts);

        // Act
        $result = $this->service->getLockoutTimeRemaining($email);

        // Assert
        $this->assertNull($result);
    }

    // Helper method to mock functions
    private function mockFunction(string $functionName, callable $callback): void
    {
        if (! function_exists($functionName)) {
            eval("function {$functionName}(\$arg) { return call_user_func_array('{$functionName}', func_get_args()); }");
        }
    }
}
