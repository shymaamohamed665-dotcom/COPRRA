<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LocaleMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Guard and Session dependencies
        $guardMock = $this->mock(Guard::class);
        $sessionMock = $this->mock(Store::class);

        $this->app->instance(Guard::class, $guardMock);
        $this->app->instance(Store::class, $sessionMock);
    }

    public function test_locale_middleware_passes_request_successfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\LocaleMiddleware(
            $this->app[Guard::class],
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_locale_middleware_handles_authenticated_user(): void
    {
        $user = \App\Models\User::factory()->create();
        $guardMock = $this->app[Guard::class];
        $guardMock->shouldReceive('check')->andReturn(true);
        $guardMock->shouldReceive('user')->andReturn($user);

        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\LocaleMiddleware(
            $guardMock,
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_locale_middleware_handles_session_locale(): void
    {
        $sessionMock = $this->app[Store::class];
        $sessionMock->shouldReceive('has')->with('locale_language')->andReturn(true);
        $sessionMock->shouldReceive('get')->with('locale_language')->andReturn('es');

        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\LocaleMiddleware(
            $this->app[Guard::class],
            $sessionMock
        );
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_locale_middleware_handles_browser_language(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('HTTP_ACCEPT_LANGUAGE', 'fr-FR,fr;q=0.9,en;q=0.8');

        $middleware = new \App\Http\Middleware\LocaleMiddleware(
            $this->app[Guard::class],
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_locale_middleware_handles_exceptions_gracefully(): void
    {
        // Mock the Guard to throw an exception
        $guardMock = $this->mock(Guard::class);
        $guardMock->shouldReceive('check')->andThrow(new \Exception('Test exception'));

        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\LocaleMiddleware(
            $guardMock,
            $this->app[Store::class]
        );
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
