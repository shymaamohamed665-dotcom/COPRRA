<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class VerifyCsrfTokenTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_csrf_token_middleware_allows_valid_token(): void
    {
        $request = Request::create('/test', 'POST');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        // Generate a valid CSRF token
        $token = csrf_token();
        $request->headers->set('X-CSRF-TOKEN', $token);
        $request->merge(['_token' => $token]);

        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_verify_csrf_token_middleware_blocks_invalid_token(): void
    {
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        $request = Request::create('/test', 'POST');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        // Use an invalid CSRF token
        $request->headers->set('X-CSRF-TOKEN', 'invalid-token');
        $request->merge(['_token' => 'invalid-token']);

        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_verify_csrf_token_middleware_allows_get_requests(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_verify_csrf_token_middleware_allows_head_requests(): void
    {
        $request = Request::create('/test', 'HEAD');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_verify_csrf_token_middleware_allows_options_requests(): void
    {
        $request = Request::create('/test', 'OPTIONS');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_verify_csrf_token_middleware_blocks_post_requests_without_token(): void
    {
        $this->expectException(\Illuminate\Session\TokenMismatchException::class);

        $request = Request::create('/test', 'POST');
        $request->setLaravelSession(app('session.store'));
        app('session.store')->start();

        $middleware = $this->app->make(\App\Http\Middleware\VerifyCsrfToken::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }
}
