<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SecurityHeadersMiddlewareTest extends TestCase
{
    public function test_security_headers_middleware_adds_security_headers(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
        $this->assertTrue($response->headers->has('X-Frame-Options'));
        $this->assertTrue($response->headers->has('X-XSS-Protection'));
    }

    public function test_security_headers_middleware_handles_sensitive_routes(): void
    {
        $request = Request::create('/admin/sensitive', 'GET');

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function test_security_headers_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function test_security_headers_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function test_security_headers_middleware_handles_https_redirect(): void
    {
        $request = Request::create('http://example.com/test', 'GET');

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }
}
