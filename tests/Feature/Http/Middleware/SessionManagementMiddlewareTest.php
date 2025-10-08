<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SessionManagementMiddlewareTest extends TestCase
{
    public function test_session_management_middleware_manages_session(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new \App\Http\Middleware\SessionManagementMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_session_management_middleware_handles_session_data(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $session->put('user_id', 123);
        $session->put('last_activity', now());

        $middleware = new \App\Http\Middleware\SessionManagementMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_session_management_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new \App\Http\Middleware\SessionManagementMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_session_management_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new \App\Http\Middleware\SessionManagementMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_session_management_middleware_handles_session_timeout(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $session->put('last_activity', now()->subHours(2));

        $middleware = new \App\Http\Middleware\SessionManagementMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
