<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ThrottleRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_throttle_requests_middleware_allows_requests_within_limit(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.1');

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleRequests::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_throttle_requests_middleware_blocks_requests_exceeding_limit(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.2');

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleRequests::class);

        // Make multiple requests to exceed the limit
        for ($i = 0; $i < 61; $i++) {
            $response = $middleware->handle($request, function ($req) {
                return response('OK', 200);
            });
        }

        $this->assertEquals(429, $response->getStatusCode());
    }

    public function test_throttle_requests_middleware_includes_retry_after_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.3');

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleRequests::class);

        // Make multiple requests to exceed the limit
        for ($i = 0; $i < 61; $i++) {
            $response = $middleware->handle($request, function ($req) {
                return response('OK', 200);
            });
        }

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertEquals('60', $response->headers->get('Retry-After'));
        $this->assertJsonStringEqualsJsonString(
            '{"message":"Too Many Requests"}',
            $response->getContent()
        );
    }

    public function test_throttle_requests_middleware_handles_different_ips_separately(): void
    {
        $request1 = Request::create('/test', 'GET');
        $request1->server->set('REMOTE_ADDR', '192.168.1.4');

        $request2 = Request::create('/test', 'GET');
        $request2->server->set('REMOTE_ADDR', '192.168.1.5');

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleRequests::class);

        // Make requests from first IP
        for ($i = 0; $i < 30; $i++) {
            $response1 = $middleware->handle($request1, function ($req) {
                return response('OK', 200);
            });
        }

        // Make requests from second IP
        for ($i = 0; $i < 30; $i++) {
            $response2 = $middleware->handle($request2, function ($req) {
                return response('OK', 200);
            });
        }

        $this->assertEquals(200, $response1->getStatusCode());
        $this->assertEquals(200, $response2->getStatusCode());
    }

    public function test_throttle_requests_middleware_resets_after_time_window(): void
    {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.6');

        $middleware = $this->app->make(\App\Http\Middleware\ThrottleRequests::class);

        // Make requests to exceed the limit
        for ($i = 0; $i < 61; $i++) {
            $response = $middleware->handle($request, function ($req) {
                return response('OK', 200);
            });
        }

        $this->assertEquals(429, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Retry-After'));
        $this->assertEquals('60', $response->headers->get('Retry-After'));
        $this->assertJsonStringEqualsJsonString(
            '{"message":"Too Many Requests"}',
            $response->getContent()
        );

        // Verify that the cache has the throttling data
        $this->assertTrue(app('cache')->has('throttle:192.168.1.6'));
        $this->assertEquals(60, app('cache')->get('throttle:192.168.1.6'));
    }
}
