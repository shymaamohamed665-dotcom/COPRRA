<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Session\Store;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AuthenticateSessionTest extends TestCase
{
    use RefreshDatabase;

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_authenticate_session_middleware_allows_authenticated_users(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AuthenticateSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_authenticate_session_middleware_handles_unauthenticated_users(): void
    {
        $request = Request::create('/test', 'GET');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new \App\Http\Middleware\AuthenticateSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_authenticate_session_middleware_handles_session_data(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'GET');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $session->put('user_id', $user->id);
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AuthenticateSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_authenticate_session_middleware_handles_post_requests(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));
        $request->setUserResolver(fn () => $user);

        $middleware = new \App\Http\Middleware\AuthenticateSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_authenticate_session_middleware_handles_api_requests(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $sessionHandler = new \Illuminate\Session\FileSessionHandler(
            new \Illuminate\Filesystem\Filesystem,
            storage_path('framework/sessions'),
            120
        );
        $request->setLaravelSession($session = new Store('test', $sessionHandler));

        $middleware = new \App\Http\Middleware\AuthenticateSession;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
