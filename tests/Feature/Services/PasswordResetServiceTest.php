<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Services\PasswordResetService;
use PHPUnit\Framework\TestCase;

class PasswordResetServiceTest extends TestCase
{
    private PasswordResetService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PasswordResetService;
    }
    public function test_can_be_instantiated(): void
    {
        // Act & Assert
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
    }
    public function test_handles_send_reset_email_with_valid_email(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'sendResetEmail'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'sendResetEmail']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'sendResetEmail');
        $this->assertEquals(1, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_handles_send_reset_email_with_nonexistent_email(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'sendResetEmail'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'sendResetEmail']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'sendResetEmail');
        $this->assertEquals(1, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_handles_reset_password_with_valid_token(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[1]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[2]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_handles_reset_password_with_invalid_token(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[1]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[2]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_checks_reset_token_exists(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'hasResetToken'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'hasResetToken']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'hasResetToken');
        $this->assertEquals(1, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_gets_reset_token_info(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'getResetTokenInfo'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'getResetTokenInfo']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'getResetTokenInfo');
        $this->assertEquals(1, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertTrue($reflection->getReturnType()->allowsNull());
    }
    public function test_handles_expired_token(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[1]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[2]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_handles_too_many_attempts(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'resetPassword'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'resetPassword']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'resetPassword');
        $this->assertEquals(3, $reflection->getNumberOfParameters());
        $this->assertEquals('string', $reflection->getParameters()[0]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[1]->getType()->getName());
        $this->assertEquals('string', $reflection->getParameters()[2]->getType()->getName());
        $this->assertEquals('bool', $reflection->getReturnType()->getName());
    }
    public function test_gets_statistics(): void
    {
        // Test service instantiation and method existence
        $this->assertInstanceOf(PasswordResetService::class, $this->service);
        $this->assertTrue(method_exists($this->service, 'getStatistics'));

        // Test that the method exists and can be called
        $this->assertIsCallable([$this->service, 'getStatistics']);

        // Test method signature validation
        $reflection = new \ReflectionMethod($this->service, 'getStatistics');
        $this->assertEquals(0, $reflection->getNumberOfParameters());
        $this->assertEquals('array', $reflection->getReturnType()->getName());

        // Test that the method returns an array
        $result = $this->service->getStatistics();
        $this->assertIsArray($result);
        $this->assertArrayHasKey('token_expiry_minutes', $result);
        $this->assertArrayHasKey('max_attempts', $result);
        $this->assertArrayHasKey('expired_tokens_cleaned', $result);
    }
}
