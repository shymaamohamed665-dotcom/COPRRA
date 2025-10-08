<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class EnsureEmailIsVerifiedTest extends TestCase
{
    public function test_ensure_email_is_verified_middleware_allows_verified_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\EnsureEmailIsVerified;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_ensure_email_is_verified_middleware_redirects_unverified_users(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\EnsureEmailIsVerified;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_ensure_email_is_verified_middleware_handles_unauthenticated_users(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\EnsureEmailIsVerified;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_ensure_email_is_verified_middleware_handles_api_requests(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => null,
        ]);
        $this->actingAs($user);

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\EnsureEmailIsVerified;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(403, $response->getStatusCode());
    }

    public function test_ensure_email_is_verified_middleware_handles_post_requests(): void
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\EnsureEmailIsVerified;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
