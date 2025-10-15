<?php

namespace Tests\Feature\Http\Middleware;

use App\Services\Security\SecurityHeadersService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Mockery;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SecurityHeadersMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_middleware_adds_security_headers(): void
    {
        $request = Request::create('/test', 'GET');

        $service = Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
            $response->headers->set('X-Frame-Options', 'DENY');
            $response->headers->set('X-XSS-Protection', '1; mode=block');
        });

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware($service);
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

        $service = Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware($service);
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

        $service = Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware($service);
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

        $service = Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }

    public function test_security_headers_middleware_handles_https_redirect(): void
    {
        $request = Request::create('http://example.com/test', 'GET');

        $service = Mockery::mock(SecurityHeadersService::class);
        $service->shouldReceive('applySecurityHeaders')->once()->andReturnUsing(function ($response, $request) {
            $response->headers->set('X-Content-Type-Options', 'nosniff');
        });

        $middleware = new \App\Http\Middleware\SecurityHeadersMiddleware($service);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('X-Content-Type-Options'));
    }
}
