<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Tests\TestCase;

class SetCacheHeadersTest extends TestCase
{
    public function test_set_cache_headers_middleware_adds_cache_headers(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\SetCacheHeaders;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Cache-Control'));
    }

    public function test_set_cache_headers_middleware_passes_request_successfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\SetCacheHeaders;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_set_cache_headers_middleware_handles_different_response_codes(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\SetCacheHeaders;
        $response = $middleware->handle($request, function ($req) {
            return response('Not Found', 404);
        });

        $this->assertEquals(404, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Cache-Control'));
    }

    public function test_set_cache_headers_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = new \App\Http\Middleware\SetCacheHeaders;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Cache-Control'));
    }

    public function test_set_cache_headers_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\SetCacheHeaders;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Cache-Control'));
    }
}
