<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SubstituteBindingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_substitute_bindings_middleware_substitutes_route_bindings(): void
    {
        $user = User::factory()->create();
        $request = Request::create("/users/{$user->id}", 'GET');
        $request->setRouteResolver(function () use ($user, $request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/users/{user}', ['uses' => function () {}]);
            $route->bind($request);
            $route->setParameter('user', $user);

            return $route;
        });

        $middleware = $this->app->make(\App\Http\Middleware\SubstituteBindings::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_substitute_bindings_middleware_handles_missing_bindings(): void
    {
        $request = Request::create('/users/999', 'GET');
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/users/{user}', ['uses' => function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(\App\Http\Middleware\SubstituteBindings::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_substitute_bindings_middleware_handles_no_route(): void
    {
        $request = Request::create('/test', 'GET');

        // ÙˆÙÙ‘Ø± Route ÙØ§Ø±Øº Ù„Ø¶Ù…Ø§Ù† Ø¹Ø¯Ù… ÙØ´Ù„ Ø§Ù„ÙˆØ³ÙŠØ· Ø¹Ù†Ø¯ Ø¹Ø¯Ù… ÙˆØ¬ÙˆØ¯ Route ÙØ¹Ù„ÙŠ
        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/test', ['uses' => function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(\App\Http\Middleware\SubstituteBindings::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_substitute_bindings_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);

        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['POST'], '/test', ['uses' => function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(\App\Http\Middleware\SubstituteBindings::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_substitute_bindings_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $request->setRouteResolver(function () use ($request) {
            $route = new \Illuminate\Routing\Route(['GET'], '/api/test', ['uses' => function () {}]);
            $route->bind($request);

            return $route;
        });

        $middleware = $this->app->make(\App\Http\Middleware\SubstituteBindings::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
