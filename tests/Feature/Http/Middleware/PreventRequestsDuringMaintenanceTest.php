<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class PreventRequestsDuringMaintenanceTest extends TestCase
{
    public function test_prevent_requests_during_maintenance_middleware_allows_requests_when_not_in_maintenance(): void
    {
        $request = Request::create('/test', 'GET');

        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(false);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new \App\Http\Middleware\PreventRequestsDuringMaintenance($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());

        // Verify that maintenance mode was checked
        $maintenanceMode->shouldHaveReceived('active')->once();

        // Verify the response is not a maintenance page
        $this->assertStringNotContainsString('maintenance', strtolower($response->getContent()));
        $this->assertStringNotContainsString('503', $response->getContent());
    }

    public function test_prevent_requests_during_maintenance_middleware_blocks_requests_during_maintenance(): void
    {
        $request = Request::create('/test', 'GET');

        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(true);
        $maintenanceMode->shouldReceive('data')->andReturn(['message' => 'Site is under maintenance']);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new \App\Http\Middleware\PreventRequestsDuringMaintenance($app);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Service Unavailable');

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        // Verify that maintenance mode was checked
        $maintenanceMode->shouldHaveReceived('active')->once();
    }

    public function test_prevent_requests_during_maintenance_middleware_handles_maintenance_mode_with_exceptions(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '127.0.0.1');

        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(true);
        $maintenanceMode->shouldReceive('data')->andReturn(['message' => 'Site is under maintenance']);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new \App\Http\Middleware\PreventRequestsDuringMaintenance($app);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);
        $this->expectExceptionMessage('Service Unavailable');

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_prevent_requests_during_maintenance_middleware_handles_different_request_methods(): void
    {
        $request = Request::create('/test', 'POST', ['data' => 'test']);

        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(false);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new \App\Http\Middleware\PreventRequestsDuringMaintenance($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_prevent_requests_during_maintenance_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $app = \Mockery::mock(\Illuminate\Contracts\Foundation\Application::class);
        $maintenanceMode = \Mockery::mock();
        $maintenanceMode->shouldReceive('active')->andReturn(false);
        $app->shouldReceive('maintenanceMode')->andReturn($maintenanceMode);

        $middleware = new \App\Http\Middleware\PreventRequestsDuringMaintenance($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());

        // Verify that maintenance mode was checked
        $maintenanceMode->shouldHaveReceived('active')->once();

        // Verify the request path is preserved
        $this->assertEquals('/api/test', $request->getPathInfo());

        // Verify the response content type is appropriate
        $this->assertStringNotContainsString('maintenance', strtolower($response->getContent()));
    }
}
