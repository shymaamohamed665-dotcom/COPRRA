<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Controllers\Api;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class DocumentationControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;
    public function test_can_get_api_status()
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'message',
                'version',
                'timestamp',
            ])
            ->assertJson([
                'status' => 'success',
                'message' => 'COPRRA API is running',
                'version' => '1.0.0',
            ]);
    }
    public function test_can_get_health_status()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'status',
                'timestamp',
                'version',
                'environment',
                'database',
                'cache',
                'storage',
            ])
            ->assertJson([
                'status' => 'healthy',
                'database' => 'connected',
                'cache' => 'working',
                'storage' => 'writable',
            ]);
    }
    public function test_returns_unhealthy_status_when_database_fails()
    {
        // Mock database connection failure
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new \Exception('Database connection failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'database' => 'disconnected',
            ]);
    }
    public function test_returns_unhealthy_status_when_cache_fails()
    {
        // Mock cache failure
        Cache::shouldReceive('put')
            ->andThrow(new \Exception('Cache failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'cache' => 'not_working',
            ]);
    }
    public function test_returns_unhealthy_status_when_storage_is_not_writable()
    {
        // Mock storage as not writable
        $this->app->instance('filesystem', function () {
            return new class
            {
                public function isWritable($path)
                {
                    return false;
                }
            };
        });

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'storage' => 'not_writable',
            ]);
    }
    public function test_includes_timestamp_in_status_response()
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
            ]);

        // Verify timestamp is valid ISO format
        $timestamp = $response->json('timestamp');
        $this->assertIsString($timestamp);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/', $timestamp);
    }
    public function test_includes_timestamp_in_health_response()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'timestamp',
            ]);

        // Verify timestamp is valid ISO format
        $timestamp = $response->json('timestamp');
        $this->assertIsString($timestamp);
        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}\.\d{6}Z$/', $timestamp);
    }
    public function test_includes_version_in_status_response()
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'version',
            ]);

        $version = $response->json('version');
        $this->assertIsString($version);
        $this->assertEquals('1.0.0', $version);
    }
    public function test_includes_version_in_health_response()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'version',
            ]);

        $version = $response->json('version');
        $this->assertIsString($version);
        $this->assertNotEmpty($version);
    }
    public function test_includes_environment_in_health_response()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'environment',
            ]);

        $environment = $response->json('environment');
        $this->assertIsString($environment);
        $this->assertContains($environment, ['local', 'testing', 'staging', 'production']);
    }
    public function test_tests_database_connection_in_health_check()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $database = $response->json('database');
        $this->assertContains($database, ['connected', 'disconnected']);
    }
    public function test_tests_cache_functionality_in_health_check()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $cache = $response->json('cache');
        $this->assertContains($cache, ['working', 'not_working']);
    }
    public function test_tests_storage_writability_in_health_check()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);

        $storage = $response->json('storage');
        $this->assertContains($storage, ['writable', 'not_writable']);
    }
    public function test_handles_multiple_system_failures_in_health_check()
    {
        // Mock both database and cache failures
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new \Exception('Database connection failed'));

        Cache::shouldReceive('put')
            ->andThrow(new \Exception('Cache failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'database' => 'disconnected',
                'cache' => 'not_working',
            ]);
    }
    public function test_returns_200_for_healthy_systems()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200);
    }
    public function test_returns_503_for_unhealthy_systems()
    {
        // Mock database failure
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new \Exception('Database connection failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503);
    }
    public function test_handles_cache_test_successfully()
    {
        // Mock successful cache test
        Cache::shouldReceive('put')
            ->with('health_check', 'ok', 60)
            ->once();

        Cache::shouldReceive('get')
            ->with('health_check')
            ->andReturn('ok');

        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'cache' => 'working',
            ]);
    }
    public function test_handles_cache_test_failure()
    {
        // Mock cache test failure
        Cache::shouldReceive('put')
            ->with('health_check', 'ok', 60)
            ->once();

        Cache::shouldReceive('get')
            ->with('health_check')
            ->andReturn(null);

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'cache' => 'not_working',
            ]);
    }
    public function test_handles_database_connection_exception()
    {
        // Mock database connection exception
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new \Exception('Connection failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'database' => 'disconnected',
            ]);
    }
    public function test_handles_cache_exception()
    {
        // Mock cache exception
        Cache::shouldReceive('put')
            ->andThrow(new \Exception('Cache failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'cache' => 'not_working',
            ]);
    }
    public function test_handles_storage_exception()
    {
        // Mock storage exception
        $this->app->instance('filesystem', function () {
            throw new \Exception('Storage failed');
        });

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
                'storage' => 'not_writable',
            ]);
    }
    public function test_returns_consistent_status_message()
    {
        $response = $this->getJson('/api/');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'success',
                'message' => 'COPRRA API is running',
            ]);
    }
    public function test_returns_consistent_health_message_for_healthy_systems()
    {
        $response = $this->getJson('/api/health');

        $response->assertStatus(200)
            ->assertJson([
                'status' => 'healthy',
            ]);
    }
    public function test_returns_consistent_health_message_for_unhealthy_systems()
    {
        // Mock database failure
        DB::shouldReceive('connection->getPdo')
            ->andThrow(new \Exception('Database connection failed'));

        $response = $this->getJson('/api/health');

        $response->assertStatus(503)
            ->assertJson([
                'status' => 'unhealthy',
            ]);
    }
}
