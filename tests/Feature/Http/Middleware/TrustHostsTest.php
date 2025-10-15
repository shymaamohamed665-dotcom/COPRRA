<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class TrustHostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_trust_hosts_middleware_trusts_valid_hosts(): void
    {
        $request = Request::create('https://example.com/test', 'GET');
        $request->headers->set('Host', 'example.com');

        $app = app(\Illuminate\Contracts\Foundation\Application::class);
        $middleware = new \App\Http\Middleware\TrustHosts($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_hosts_middleware_handles_different_hosts(): void
    {
        $request = Request::create('https://subdomain.example.com/test', 'GET');
        $request->headers->set('Host', 'subdomain.example.com');

        $app = app(\Illuminate\Contracts\Foundation\Application::class);
        $middleware = new \App\Http\Middleware\TrustHosts($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_hosts_middleware_handles_localhost(): void
    {
        $request = Request::create('http://localhost/test', 'GET');
        $request->headers->set('Host', 'localhost');

        $app = app(\Illuminate\Contracts\Foundation\Application::class);
        $middleware = new \App\Http\Middleware\TrustHosts($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_hosts_middleware_handles_post_requests(): void
    {
        $request = Request::create('https://example.com/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Host', 'example.com');

        $app = app(\Illuminate\Contracts\Foundation\Application::class);
        $middleware = new \App\Http\Middleware\TrustHosts($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_trust_hosts_middleware_handles_api_requests(): void
    {
        $request = Request::create('https://api.example.com/test', 'GET');
        $request->headers->set('Host', 'api.example.com');
        $request->headers->set('Accept', 'application/json');

        $app = app(\Illuminate\Contracts\Foundation\Application::class);
        $middleware = new \App\Http\Middleware\TrustHosts($app);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
