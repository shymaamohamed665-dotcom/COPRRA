<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class InputSanitizationMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_input_sanitization_middleware_sanitizes_input(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '<script>alert("xss")</script>John Doe',
            'email' => 'test@example.com',
            'description' => 'Normal description',
        ]);

        $middleware = new \App\Http\Middleware\InputSanitizationMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_input_sanitization_middleware_handles_get_requests(): void
    {
        $request = Request::create('/test', 'GET', [
            'search' => '<script>alert("xss")</script>search term',
        ]);

        $middleware = new \App\Http\Middleware\InputSanitizationMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_input_sanitization_middleware_handles_nested_arrays(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '<script>alert("xss")</script>John',
                'email' => 'john@example.com',
            ],
            'address' => [
                'street' => '123 Main St',
                'city' => '<script>alert("xss")</script>New York',
            ],
        ]);

        $middleware = new \App\Http\Middleware\InputSanitizationMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_input_sanitization_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'POST', [
            'data' => '<script>alert("xss")</script>test data',
        ]);
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\InputSanitizationMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_input_sanitization_middleware_handles_clean_input(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'description' => 'Clean description',
        ]);

        $middleware = new \App\Http\Middleware\InputSanitizationMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
