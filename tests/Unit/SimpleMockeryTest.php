<?php

namespace Tests\Unit;

use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SimpleMockeryTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mock_basic_functionality(): void
    {
        // Arrange
        $mock = Mockery::mock();
        $mock->shouldReceive('getValue')->andReturn('mocked value');

        // Act
        $result = $mock->getValue();

        // Assert
        $this->assertEquals('mocked value', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mock_expected_behavior_with_parameters(): void
    {
        // Arrange
        $mock = Mockery::mock();
        $mock->shouldReceive('processData')
            ->with('input')
            ->andReturn('processed output');

        // Act
        $result = $mock->processData('input');

        // Assert
        $this->assertEquals('processed output', $result);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_mock_validation_throws_exception(): void
    {
        // Arrange
        $mock = Mockery::mock();
        $mock->shouldReceive('validate')
            ->with('invalid')
            ->andThrow(new \InvalidArgumentException('Invalid input'));

        // Act & Assert
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid input');
        $mock->validate('invalid');
    }
}
