<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_middleware_allows_authenticated_admin_user(): void
    {
        $user = User::factory()->create(['is_admin' => true]);
        $this->actingAs($user);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AdminMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_middleware_redirects_unauthenticated_user(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');

        $middleware = new \App\Http\Middleware\AdminMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_middleware_returns_json_for_api_requests_when_unauthenticated(): void
    {
        $request = Request::create('/api/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\AdminMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Unauthenticated', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_middleware_redirects_non_admin_user(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AdminMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/', $response->headers->get('Location'));
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_middleware_returns_json_for_api_requests_when_non_admin(): void
    {
        $user = User::factory()->create(['is_admin' => false]);
        $this->actingAs($user);

        $request = Request::create('/api/admin/dashboard', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AdminMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());
        $this->assertStringContainsString('Forbidden', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_admin_middleware_handles_null_user(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new \App\Http\Middleware\AdminMiddleware;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('/login', $response->headers->get('Location'));
    }
}
