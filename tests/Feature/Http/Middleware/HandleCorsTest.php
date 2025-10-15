<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HandleCorsTest extends TestCase
{
    use RefreshDatabase;

    public function test_handle_cors_middleware_adds_cors_headers(): void
    {
        $request = Request::create('/api/test', 'OPTIONS');
        $request->headers->set('Origin', 'https://example.com');
        $request->headers->set('Access-Control-Request-Method', 'POST');

        $middleware = $this->app->make(\App\Http\Middleware\HandleCors::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Access-Control-Allow-Origin'));
        $this->assertTrue($response->headers->has('Access-Control-Allow-Methods'));
        $this->assertTrue($response->headers->has('Access-Control-Allow-Headers'));
    }

    public function test_handle_cors_middleware_handles_preflight_requests(): void
    {
        $request = Request::create('/api/test', 'OPTIONS');
        $request->headers->set('Origin', 'https://example.com');
        $request->headers->set('Access-Control-Request-Method', 'POST');
        $request->headers->set('Access-Control-Request-Headers', 'Content-Type, Authorization');

        $middleware = $this->app->make(\App\Http\Middleware\HandleCors::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertStringContainsString('POST', $response->headers->get('Access-Control-Allow-Methods'));
        $this->assertStringContainsString('Content-Type', $response->headers->get('Access-Control-Allow-Headers'));
    }

    public function test_handle_cors_middleware_passes_regular_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Origin', 'https://example.com');

        $middleware = $this->app->make(\App\Http\Middleware\HandleCors::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
