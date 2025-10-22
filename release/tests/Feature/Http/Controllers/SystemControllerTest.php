<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Artisan;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SystemControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    #[Test]
    public function it_can_get_system_information(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'laravel_version',
                    'php_version',
                    'os',
                    'server_software',
                    'memory_limit',
                    'max_execution_time',
                    'disk_free_space',
                    'disk_total_space',
                    'uptime',
                    'cpu_count',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'System information retrieved successfully',
            ]);

        $this->assertArrayHasKey('load_average', $response->json('data'));
    }

    #[Test]
    public function it_can_run_database_migrations(): void
    {
        $response = $this->postJson('/api/system/migrations');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'output',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Migrations ran successfully',
            ]);
    }

    #[Test]
    public function it_can_clear_application_cache(): void
    {
        $response = $this->postJson('/api/system/cache/clear');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Cache cleared successfully',
            ]);
    }

    #[Test]
    public function it_can_optimize_application(): void
    {
        $response = $this->postJson('/api/system/optimize');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Application optimized successfully',
            ]);
    }

    #[Test]
    public function it_can_run_composer_update(): void
    {
        $response = $this->postJson('/api/system/composer-update');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'output',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Composer update ran successfully',
            ]);
    }

    #[Test]
    public function it_can_get_performance_metrics(): void
    {
        $response = $this->getJson('/api/system/performance');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'memory_usage',
                    'memory_peak',
                    'memory_limit',
                    'execution_time',
                    'database_connections',
                    'cache_hits',
                    'response_time',
                ],
                'message',
            ])
            ->assertJson([
                'success' => true,
                'message' => 'Performance metrics retrieved successfully',
            ]);
    }

    #[Test]
    public function it_handles_migration_errors(): void
    {
        // Mock Artisan::call failure
        Artisan::shouldReceive('call')
            ->with('migrate', ['--force' => true])
            ->andThrow(new \Exception('Migration failed'));

        $response = $this->postJson('/api/system/migrations');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to run migrations',
            ]);
    }

    #[Test]
    public function it_handles_cache_clear_errors(): void
    {
        // Mock Artisan::call failure
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->andThrow(new \Exception('Cache clear failed'));

        $response = $this->postJson('/api/system/cache/clear');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to clear cache',
            ]);
    }

    #[Test]
    public function it_handles_optimization_errors(): void
    {
        // Mock Artisan::call failure
        Artisan::shouldReceive('call')
            ->with('optimize')
            ->andThrow(new \Exception('Optimization failed'));

        $response = $this->postJson('/api/system/optimize');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to optimize application',
            ]);
    }

    #[Test]
    public function it_handles_composer_update_errors(): void
    {
        // Mock Process failure
        $this->mock(\Symfony\Component\Process\Process::class, function ($mock) {
            $mock->shouldReceive('setTimeout')->andReturnSelf();
            $mock->shouldReceive('run')->andReturnSelf();
            $mock->shouldReceive('isSuccessful')->andReturn(false);
            $mock->shouldReceive('getErrorOutput')->andReturn('Composer update failed');
        });

        $response = $this->postJson('/api/system/composer-update');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to run composer update',
            ]);
    }

    #[Test]
    public function it_handles_system_info_errors(): void
    {
        // Mock system info failure
        $this->mock(\App\Http\Controllers\SystemController::class, function ($mock) {
            $mock->shouldReceive('getSystemInfo')
                ->andThrow(new \Exception('System info failed'));
        });

        $response = $this->getJson('/api/system/info');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to get system information',
            ]);
    }

    #[Test]
    public function it_handles_performance_metrics_errors(): void
    {
        // Mock performance metrics failure
        $this->mock(\App\Http\Controllers\SystemController::class, function ($mock) {
            $mock->shouldReceive('getPerformanceMetrics')
                ->andThrow(new \Exception('Performance metrics failed'));
        });

        $response = $this->getJson('/api/system/performance');

        $response->assertStatus(500)
            ->assertJson([
                'success' => false,
                'message' => 'Failed to get performance metrics',
            ]);
    }

    #[Test]
    public function it_calls_multiple_artisan_commands_for_cache_clear(): void
    {
        Artisan::shouldReceive('call')
            ->with('cache:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('config:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('view:clear')
            ->once();

        Artisan::shouldReceive('call')
            ->with('route:clear')
            ->once();

        $response = $this->postJson('/api/system/cache/clear');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_returns_valid_system_information(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify required fields exist
        $this->assertArrayHasKey('laravel_version', $data);
        $this->assertArrayHasKey('php_version', $data);
        $this->assertArrayHasKey('os', $data);
        $this->assertArrayHasKey('memory_limit', $data);
        $this->assertArrayHasKey('disk_free_space', $data);
        $this->assertArrayHasKey('disk_total_space', $data);

        // Verify data types
        $this->assertIsString($data['laravel_version']);
        $this->assertIsString($data['php_version']);
        $this->assertIsString($data['os']);
        $this->assertIsString($data['memory_limit']);
        $this->assertIsString($data['disk_free_space']);
        $this->assertIsString($data['disk_total_space']);
    }

    #[Test]
    public function it_returns_valid_performance_metrics(): void
    {
        $response = $this->getJson('/api/system/performance');

        $response->assertStatus(200);

        $data = $response->json('data');

        // Verify required fields exist
        $this->assertArrayHasKey('memory_usage', $data);
        $this->assertArrayHasKey('memory_peak', $data);
        $this->assertArrayHasKey('memory_limit', $data);
        $this->assertArrayHasKey('execution_time', $data);
        $this->assertArrayHasKey('database_connections', $data);
        $this->assertArrayHasKey('cache_hits', $data);
        $this->assertArrayHasKey('response_time', $data);

        // Verify data types
        $this->assertIsInt($data['memory_usage']);
        $this->assertIsInt($data['memory_peak']);
        $this->assertIsString($data['memory_limit']);
        $this->assertIsFloat($data['execution_time']);
        $this->assertIsInt($data['database_connections']);
        $this->assertIsInt($data['cache_hits']);
        $this->assertIsFloat($data['response_time']);
    }

    #[Test]
    public function it_handles_uptime_calculation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('uptime', $data);
        $this->assertIsString($data['uptime']);
    }

    #[Test]
    public function it_handles_load_average_calculation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('load_average', $data);
        $this->assertTrue(is_array($data['load_average']) || is_string($data['load_average']), 'load_average is not an array or a string');
    }

    #[Test]
    public function it_handles_cpu_count_calculation(): void
    {
        $response = $this->getJson('/api/system/info');

        $response->assertStatus(200);

        $data = $response->json('data');
        $this->assertArrayHasKey('cpu_count', $data);
        $this->assertIsInt($data['cpu_count']);
        $this->assertGreaterThan(0, $data['cpu_count']);
    }
}
