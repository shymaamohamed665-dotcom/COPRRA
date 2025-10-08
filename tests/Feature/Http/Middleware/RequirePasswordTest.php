<?php

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class RequirePasswordTest extends TestCase
{
    public function test_require_password_middleware_allows_requests_with_valid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($user);

        // Manually set the password_confirmed_at attribute
        $user->password_confirmed_at = now();
        $user->save();

        $request = Request::create('/test', 'POST', [
            'password' => 'password123',
        ]);
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\RequirePassword;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_require_password_middleware_blocks_requests_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'password' => 'wrongpassword',
        ]);
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\RequirePassword;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_require_password_middleware_blocks_requests_without_password(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', []);
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\RequirePassword;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_require_password_middleware_handles_unauthenticated_users(): void
    {
        $request = Request::create('/test', 'POST', [
            'password' => 'password123',
        ]);

        $middleware = new \App\Http\Middleware\RequirePassword;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
    }

    public function test_require_password_middleware_handles_get_requests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        // Manually set the password_confirmed_at attribute
        $user->password_confirmed_at = now();
        $user->save();

        $request = Request::create('/test', 'GET');
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\RequirePassword;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
