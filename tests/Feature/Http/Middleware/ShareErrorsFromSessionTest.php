<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\SafeMiddlewareTestBase;

class ShareErrorsFromSessionTest extends SafeMiddlewareTestBase
{
    public function test_share_errors_from_session_middleware_shares_errors(): void
    {
        $request = Request::create('/test', 'GET');
        $handler = new \Illuminate\Session\NullSessionHandler;
        $request->setLaravelSession($session = new Store('test', $handler));
        $session->put('errors', ['name' => ['The name field is required.']]);

        $middleware = new \App\Http\Middleware\ShareErrorsFromSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_share_errors_from_session_middleware_handles_no_errors(): void
    {
        $request = Request::create('/test', 'GET');
        $handler = new \Illuminate\Session\NullSessionHandler;
        $request->setLaravelSession($session = new Store('test', $handler));

        $middleware = new \App\Http\Middleware\ShareErrorsFromSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_share_errors_from_session_middleware_handles_multiple_errors(): void
    {
        $request = Request::create('/test', 'GET');
        $handler = new \Illuminate\Session\NullSessionHandler;
        $request->setLaravelSession($session = new Store('test', $handler));
        $session->put('errors', [
            'name' => ['The name field is required.'],
            'email' => ['The email field is required.', 'The email must be a valid email address.'],
        ]);

        $middleware = new \App\Http\Middleware\ShareErrorsFromSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_share_errors_from_session_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $handler = new \Illuminate\Session\NullSessionHandler;
        $request->setLaravelSession($session = new Store('test', $handler));

        $middleware = new \App\Http\Middleware\ShareErrorsFromSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_share_errors_from_session_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $handler = new \Illuminate\Session\NullSessionHandler;
        $request->setLaravelSession($session = new Store('test', $handler));

        $middleware = new \App\Http\Middleware\ShareErrorsFromSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
