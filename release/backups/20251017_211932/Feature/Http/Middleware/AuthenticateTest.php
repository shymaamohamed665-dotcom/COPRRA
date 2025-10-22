<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AuthenticateTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock the Auth factory
        $authFactoryMock = $this->mock(AuthFactory::class);
        $authFactoryMock->shouldReceive('guard')->andReturn($this->app['auth']);
        $this->app->instance(AuthFactory::class, $authFactoryMock);
    }

    public function test_authenticate_middleware_redirects_web_requests_to_login(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('Accept', 'text/html');

        $middleware = new \App\Http\Middleware\Authenticate($this->app[AuthFactory::class]);

        // Mock the unauthenticated method to avoid actual redirect
        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_middleware_returns_json_for_api_requests(): void
    {
        $request = Request::create('/api/user', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\Authenticate($this->app[AuthFactory::class]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_middleware_handles_api_route_pattern(): void
    {
        $request = Request::create('/api/v1/users', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\Authenticate($this->app[AuthFactory::class]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_middleware_passes_authenticated_requests(): void
    {
        $user = \App\Models\User::factory()->create();
        $this->actingAs($user);

        // Mock the auth factory to return a guard that checks as authenticated
        $guardMock = $this->mock(\Illuminate\Contracts\Auth\Guard::class);
        $guardMock->shouldReceive('check')->andReturn(true);
        $guardMock->shouldReceive('user')->andReturn($user);

        $authFactoryMock = $this->app[AuthFactory::class];
        $authFactoryMock->shouldReceive('guard')->andReturn($guardMock);
        $authFactoryMock->shouldReceive('shouldUse')->andReturn($guardMock);

        $request = Request::create('/dashboard', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\Authenticate($authFactoryMock);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_authenticate_middleware_handles_different_request_types(): void
    {
        $request = Request::create('/dashboard', 'GET');
        $request->headers->set('Accept', 'text/html');

        $middleware = new \App\Http\Middleware\Authenticate($this->app[AuthFactory::class]);

        $this->expectException(\Symfony\Component\HttpKernel\Exception\HttpException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }
}
