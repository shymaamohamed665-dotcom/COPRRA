<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\DTO\ProcessResult;
use App\Services\ProcessService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use Mockery;
use Tests\TestCase;

class ProcessServiceTest extends TestCase
{
    use RefreshDatabase;

    private ProcessService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProcessService;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_runs_process_command_successfully()
    {
        // Arrange
        $command = 'ls -la';
        $exitCode = 0;
        $output = 'total 0';
        $errorOutput = '';

        $processResult = Mockery::mock();
        $processResult->shouldReceive('exitCode')->andReturn($exitCode);
        $processResult->shouldReceive('output')->andReturn($output);
        $processResult->shouldReceive('errorOutput')->andReturn($errorOutput);

        // Support chaining: Process::timeout(...)->run($command)
        Process::shouldReceive('timeout')
            ->with(Mockery::type('int'))
            ->andReturnSelf();

        Process::shouldReceive('run')
            ->with($command)
            ->andReturn($processResult);

        // Act
        $result = $this->service->run($command);

        // Assert
        $this->assertInstanceOf(ProcessResult::class, $result);
        $this->assertEquals($exitCode, $result->exitCode);
        $this->assertEquals($output, $result->output);
        $this->assertEquals($errorOutput, $result->errorOutput);
    }

    public function test_processes_data_successfully()
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->process($data);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['processed']);
        $this->assertArrayHasKey('data', $result);
    }

    public function test_handles_null_data()
    {
        // Act
        $result = $this->service->process(null);

        // Assert
        $this->assertIsArray($result);
        $this->assertTrue($result['error']);
        $this->assertEquals('Invalid data provided', $result['message']);
    }

    public function test_validates_data_successfully()
    {
        // Arrange
        $data = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->validate($data);

        // Assert
        $this->assertTrue($result);
    }

    public function test_validates_data_with_empty_name()
    {
        // Arrange
        $data = [
            'name' => '',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->validate($data);

        // Assert
        $this->assertFalse($result);
        $errors = $this->service->getErrors();
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_cleans_data_successfully()
    {
        // Arrange
        $data = [
            'name' => '  John Doe  ',
            'email' => '  JOHN@EXAMPLE.COM  ',
        ];

        // Act
        $result = $this->service->clean($data);

        // Assert
        $this->assertEquals('John Doe', $result['name']);
        $this->assertEquals('john@example.com', $result['email']);
    }

    public function test_transforms_data_successfully()
    {
        // Arrange
        $data = [
            'name' => 'john doe',
            'email' => 'john@example.com',
        ];

        // Act
        $result = $this->service->transform($data);

        // Assert
        $this->assertEquals('John doe', $result['name']);
        $this->assertEquals('John@example.com', $result['email']);
    }

    public function test_gets_processing_status()
    {
        // Act
        $status = $this->service->getStatus();

        // Assert
        $this->assertEquals('idle', $status);
    }

    public function test_resets_service()
    {
        // Arrange
        $data = ['name' => ''];
        $this->service->process($data);

        // Act
        $this->service->reset();

        // Assert
        $this->assertEquals('idle', $this->service->getStatus());
        $this->assertEmpty($this->service->getErrors());
    }

    public function test_gets_processing_metrics()
    {
        // Arrange
        $data = ['name' => 'John Doe', 'email' => 'john@example.com'];
        $this->service->process($data);

        // Act
        $metrics = $this->service->getMetrics();

        // Assert
        $this->assertIsArray($metrics);
        $this->assertArrayHasKey('processed_count', $metrics);
        $this->assertArrayHasKey('error_count', $metrics);
    }
}
