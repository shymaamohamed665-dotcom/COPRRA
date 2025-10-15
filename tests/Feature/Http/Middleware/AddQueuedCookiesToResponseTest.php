<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
// Disabled process isolation on Windows to avoid batch execution issues
use Tests\TestCase;

class AddQueuedCookiesToResponseTest extends TestCase
{
    use RefreshDatabase;

    public function test_add_queued_cookies_middleware_adds_cookies_to_response(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\AddQueuedCookiesToResponse;
        $response = $middleware->handle($request, function ($req) {
            $response = new Response('OK', 200);
            $response->headers->setCookie(cookie('test_cookie', 'test_value'));

            return $response;
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Set-Cookie'));
    }

    public function test_add_queued_cookies_middleware_passes_request_successfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\AddQueuedCookiesToResponse;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_add_queued_cookies_middleware_handles_multiple_cookies(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\AddQueuedCookiesToResponse;
        $response = $middleware->handle($request, function ($req) {
            $response = new Response('OK', 200);
            $response->headers->setCookie(cookie('cookie1', 'value1'));
            $response->headers->setCookie(cookie('cookie2', 'value2'));

            return $response;
        });

        $this->assertEquals(200, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);
    }
}
