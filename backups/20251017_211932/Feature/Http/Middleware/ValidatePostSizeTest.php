<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ValidatePostSizeTest extends TestCase
{
    use RefreshDatabase;

    public function test_validate_post_size_middleware_allows_valid_post_size(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $middleware = new \App\Http\Middleware\ValidatePostSize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_post_size_middleware_handles_get_requests(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\ValidatePostSize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_post_size_middleware_handles_put_requests(): void
    {
        $request = Request::create('/test', 'PUT', [
            'name' => 'Updated Name',
        ]);

        $middleware = new \App\Http\Middleware\ValidatePostSize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_post_size_middleware_handles_patch_requests(): void
    {
        $request = Request::create('/test', 'PATCH', [
            'name' => 'Patched Name',
        ]);

        $middleware = new \App\Http\Middleware\ValidatePostSize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_validate_post_size_middleware_handles_delete_requests(): void
    {
        $request = Request::create('/test', 'DELETE');

        $middleware = new \App\Http\Middleware\ValidatePostSize;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
