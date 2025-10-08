<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RedirectIfAuthenticatedTest extends TestCase
{
    public function test_redirect_if_authenticated_middleware_redirects_authenticated_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/login', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\RedirectIfAuthenticated;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/', $response->headers->get('Location'));
    }

    public function test_redirect_if_authenticated_middleware_allows_unauthenticated_users(): void
    {
        $request = Request::create('/login', 'GET');

        $middleware = new \App\Http\Middleware\RedirectIfAuthenticated;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_redirect_if_authenticated_middleware_handles_api_requests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/api/login', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\RedirectIfAuthenticated;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_redirect_if_authenticated_middleware_handles_different_routes(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/register', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\RedirectIfAuthenticated;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_redirect_if_authenticated_middleware_handles_null_user(): void
    {
        $request = Request::create('/login', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new \App\Http\Middleware\RedirectIfAuthenticated;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
