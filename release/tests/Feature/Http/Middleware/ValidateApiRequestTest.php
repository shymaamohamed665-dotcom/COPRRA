<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ValidateApiRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_api_request_middleware_validates_valid_api_request(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = $this->app->make(\App\Http\Middleware\ValidateApiRequest::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_api_request_middleware_handles_post_requests(): void
    {
        $request = Request::create('/api/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = new \App\Http\Middleware\ValidateApiRequest;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_api_request_middleware_handles_put_requests(): void
    {
        $request = Request::create('/api/test/1', 'PUT', [
            'name' => 'Updated Name',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = new \App\Http\Middleware\ValidateApiRequest;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_api_request_middleware_handles_patch_requests(): void
    {
        $request = Request::create('/api/test/1', 'PATCH', [
            'name' => 'Patched Name',
        ]);
        $request->headers->set('Accept', 'application/json');
        $request->headers->set('Content-Type', 'application/json');

        $middleware = new \App\Http\Middleware\ValidateApiRequest;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_api_request_middleware_handles_delete_requests(): void
    {
        $request = Request::create('/api/test/1', 'DELETE');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ValidateApiRequest;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_api_request_middleware_handles_missing_content_type(): void
    {
        $request = Request::create('/api/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ValidateApiRequest;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
