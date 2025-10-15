<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class StartSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_start_session_middleware_starts_session(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = $this->app->make(\App\Http\Middleware\StartSession::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_start_session_middleware_handles_session_data(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setLaravelSession(app('session.store'));

        $middleware = $this->app->make(\App\Http\Middleware\StartSession::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_start_session_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $middleware = $this->app->make(\App\Http\Middleware\StartSession::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_start_session_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\StartSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_start_session_middleware_handles_different_request_methods(): void
    {
        $request = Request::create('/test', 'PUT', [
            'name' => 'Updated Name',
        ]);

        $middleware = new \App\Http\Middleware\StartSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
