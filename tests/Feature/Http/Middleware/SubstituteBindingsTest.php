<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SubstituteBindingsTest extends TestCase
{
    public function test_substitute_bindings_middleware_substitutes_route_bindings(): void
    {
        $user = User::factory()->create();
        $request = Request::create("/users/{$user->id}", 'GET');
        $request->setRouteResolver(function () use ($user) {
            $route = new \Illuminate\Routing\Route(['GET'], '/users/{user}', []);
            $route->bind($request);
            $route->setParameter('user', $user);

            return $route;
        });

        $middleware = new \App\Http\Middleware\SubstituteBindings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_substitute_bindings_middleware_handles_missing_bindings(): void
    {
        $request = Request::create('/users/999', 'GET');
        $request->setRouteResolver(function () {
            $route = new \Illuminate\Routing\Route(['GET'], '/users/{user}', []);
            $route->bind($request);

            return $route;
        });

        $middleware = new \App\Http\Middleware\SubstituteBindings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_substitute_bindings_middleware_handles_no_route(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\SubstituteBindings;
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

        $middleware = new \App\Http\Middleware\SubstituteBindings;
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

        $middleware = new \App\Http\Middleware\SubstituteBindings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
