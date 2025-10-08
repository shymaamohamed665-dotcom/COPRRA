<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class VerifyCsrfTokenTest extends TestCase
{
    public function test_verify_csrf_token_middleware_allows_valid_token(): void
    {
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($session = new Store('test'));

        // Generate a valid CSRF token
        $token = csrf_token();
        $request->headers->set('X-CSRF-TOKEN', $token);
        $request->merge(['_token' => $token]);

        $middleware = new \App\Http\Middleware\VerifyCsrfToken;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_verify_csrf_token_middleware_blocks_invalid_token(): void
    {
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($session = new Store('test'));

        // Use an invalid CSRF token
        $request->headers->set('X-CSRF-TOKEN', 'invalid-token');
        $request->merge(['_token' => 'invalid-token']);

        $middleware = new \App\Http\Middleware\VerifyCsrfToken;

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_verify_csrf_token_middleware_allows_get_requests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\VerifyCsrfToken;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_verify_csrf_token_middleware_allows_head_requests(): void
    {
        $request = Request::create('/test', 'HEAD');

        $middleware = new \App\Http\Middleware\VerifyCsrfToken;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_verify_csrf_token_middleware_allows_options_requests(): void
    {
        $request = Request::create('/test', 'OPTIONS');

        $middleware = new \App\Http\Middleware\VerifyCsrfToken;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_verify_csrf_token_middleware_blocks_post_requests_without_token(): void
    {
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession($session = new Store('test'));

        $middleware = new \App\Http\Middleware\VerifyCsrfToken;

        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }
}
