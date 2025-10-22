<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ThrottleSensitiveOperationsTest extends TestCase
{
    use RefreshDatabase;

    public function test_throttle_sensitive_operations_middleware_allows_requests_within_limit(): void
    {
        $request = Request::create('/test', 'POST', [
            'password' => 'newpassword123',
        ]);

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleSensitiveOperations::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_throttle_sensitive_operations_middleware_handles_password_change(): void
    {
        $request = Request::create('/change-password', 'POST', [
            'current_password' => 'oldpassword',
            'new_password' => 'newpassword123',
            'new_password_confirmation' => 'newpassword123',
        ]);

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleSensitiveOperations::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_throttle_sensitive_operations_middleware_handles_email_change(): void
    {
        $request = Request::create('/change-email', 'POST', [
            'email' => 'newemail@example.com',
            'password' => 'password123',
        ]);

        $middleware = new \App\Http\Middleware\ThrottleSensitiveOperations;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_throttle_sensitive_operations_middleware_handles_get_requests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\ThrottleSensitiveOperations;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_throttle_sensitive_operations_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/sensitive-operation', 'POST', [
            'data' => 'sensitive data',
        ]);
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ThrottleSensitiveOperations;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
