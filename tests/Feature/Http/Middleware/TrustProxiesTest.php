<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class TrustProxiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_trust_proxies_middleware_handles_proxy_headers(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-For', '192.168.1.1');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $middleware = new \App\Http\Middleware\TrustProxies;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_proxies_middleware_handles_https_forwarding(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-Proto', 'https');

        $middleware = new \App\Http\Middleware\TrustProxies;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_proxies_middleware_handles_port_forwarding(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-Port', '8080');

        $middleware = new \App\Http\Middleware\TrustProxies;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_proxies_middleware_handles_host_forwarding(): void
    {
        $request = Request::create('http://example.com/test', 'GET');
        $request->headers->set('X-Forwarded-Host', 'api.example.com');

        $middleware = new \App\Http\Middleware\TrustProxies;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_proxies_middleware_handles_no_proxy_headers(): void
    {
        $request = Request::create('http://example.com/test', 'GET');

        $middleware = new \App\Http\Middleware\TrustProxies;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
