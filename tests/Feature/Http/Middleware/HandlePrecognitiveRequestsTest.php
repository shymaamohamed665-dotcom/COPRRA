<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class HandlePrecognitiveRequestsTest extends TestCase
{
    public function test_handle_precognitive_requests_middleware_passes_regular_requests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\HandlePrecognitiveRequests;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_handle_precognitive_requests_middleware_handles_precognitive_header(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Precognition', 'true');

        $middleware = new \App\Http\Middleware\HandlePrecognitiveRequests;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_handle_precognitive_requests_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $middleware = new \App\Http\Middleware\HandlePrecognitiveRequests;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_handle_precognitive_requests_middleware_handles_put_requests(): void
    {
        $request = Request::create('/test', 'PUT', [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
        ]);

        $middleware = new \App\Http\Middleware\HandlePrecognitiveRequests;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_handle_precognitive_requests_middleware_handles_patch_requests(): void
    {
        $request = Request::create('/test', 'PATCH', [
            'name' => 'Updated Name',
        ]);

        $middleware = new \App\Http\Middleware\HandlePrecognitiveRequests;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
