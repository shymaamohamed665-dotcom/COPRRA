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
class AuthorizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_authorize_middleware_allows_authorized_requests(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('can')->with('test-ability')->andReturn(true);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\Authorize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());

        // Verify that the user's can() method was called with the correct ability
        $user->shouldHaveReceived('can')->with('test-ability')->once();

        // Verify the response is not a forbidden response
        $this->assertNotEquals(403, $response->getStatusCode());
        $this->assertStringNotContainsString('forbidden', strtolower($response->getContent()));
    }

    public function test_authorize_middleware_handles_unauthenticated_users(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\Authorize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        $this->assertEquals(403, $response->getStatusCode());

        // Verify the response content indicates forbidden access
        $this->assertStringContainsString('forbidden', strtolower($response->getContent()));

        // Verify the request was not processed (no OK response)
        $this->assertNotEquals('OK', $response->getContent());
    }

    public function test_authorize_middleware_handles_post_requests(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('can')->with('test-ability')->andReturn(false);
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\Authorize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        $this->assertEquals(403, $response->getStatusCode());

        // Verify that the user's can() method was called
        $user->shouldHaveReceived('can')->with('test-ability')->once();

        // Verify the response content indicates forbidden access
        $this->assertStringContainsString('forbidden', strtolower($response->getContent()));

        // Verify the POST data was not processed
        $this->assertNotEquals('OK', $response->getContent());
    }

    public function test_authorize_middleware_handles_api_requests(): void
    {
        $user = \Mockery::mock(User::class);
        $user->shouldReceive('can')->with('test-ability')->andReturn(false);
        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\Authorize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        $this->assertEquals(403, $response->getStatusCode());

        // Verify that the user's can() method was called
        $user->shouldHaveReceived('can')->with('test-ability')->once();

        // Verify the response is JSON for API requests
        $this->assertStringContainsString('application/json', $response->headers->get('Content-Type'));

        // Verify the response content indicates forbidden access
        $this->assertStringContainsString('forbidden', strtolower($response->getContent()));
    }

    public function test_authorize_middleware_handles_null_user(): void
    {
        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => null);

        $middleware = new \App\Http\Middleware\Authorize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        }, 'test-ability');

        $this->assertEquals(403, $response->getStatusCode());
    }
}
