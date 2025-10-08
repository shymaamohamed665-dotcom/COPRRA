<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AuthenticateWithBasicAuthTest extends TestCase
{
    public function test_authenticate_with_basic_auth_middleware_handles_requests(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', 'Basic '.base64_encode('test@example.com:password123'));

        $middleware = new \App\Http\Middleware\AuthenticateWithBasicAuth;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        // Basic auth middleware typically returns 401 for invalid credentials
        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_authenticate_with_basic_auth_middleware_handles_invalid_credentials(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', 'Basic '.base64_encode('test@example.com:wrongpassword'));

        $middleware = new \App\Http\Middleware\AuthenticateWithBasicAuth;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_authenticate_with_basic_auth_middleware_handles_missing_authorization(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\AuthenticateWithBasicAuth;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_authenticate_with_basic_auth_middleware_handles_malformed_authorization(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('Authorization', 'InvalidFormat');

        $middleware = new \App\Http\Middleware\AuthenticateWithBasicAuth;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(401, $response->getStatusCode());
    }

    public function test_authenticate_with_basic_auth_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('Authorization', 'Basic '.base64_encode('test@example.com:password123'));

        $middleware = new \App\Http\Middleware\AuthenticateWithBasicAuth;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        // Basic auth middleware typically returns 401 for invalid credentials
        $this->assertEquals(401, $response->getStatusCode());
    }
}
