<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ApiErrorHandlerTest extends TestCase
{
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_passes_request_successfully(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            return response('Success', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('Success', $response->getContent());
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_validation_exception(): void
    {
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw ValidationException::withMessages(['field' => ['The field is required.']]);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(422, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Validation failed', $data['message']);
        $this->assertEquals('VALIDATION_ERROR', $data['error_code']);
        $this->assertArrayHasKey('errors', $data);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_authentication_exception(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new AuthenticationException;
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(401, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Authentication required', $data['message']);
        $this->assertEquals('UNAUTHENTICATED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_authorization_exception(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new AuthorizationException('Access denied');
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(403, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Access denied', $data['message']);
        $this->assertEquals('UNAUTHORIZED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_model_not_found_exception(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new ModelNotFoundException;
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Resource not found', $data['message']);
        $this->assertEquals('NOT_FOUND', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_not_found_http_exception(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new NotFoundHttpException('Endpoint not found');
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(404, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Endpoint not found', $data['message']);
        $this->assertEquals('ENDPOINT_NOT_FOUND', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_method_not_allowed_exception(): void
    {
        $request = Request::create('/api/test', 'POST');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new MethodNotAllowedHttpException(['GET']);
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(405, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Method not allowed', $data['message']);
        $this->assertEquals('METHOD_NOT_ALLOWED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_too_many_requests_exception(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new TooManyRequestsHttpException(60, 'Rate limit exceeded');
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(429, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Too many requests', $data['message']);
        $this->assertEquals('RATE_LIMIT_EXCEEDED', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_pdo_exception(): void
    {
        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new \PDOException('Database connection failed');
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(503, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Database connection error', $data['message']);
        $this->assertEquals('DATABASE_ERROR', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_general_exception_in_production(): void
    {
        $this->app->instance('env', 'production');

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new \Exception('Something went wrong');
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Internal server error', $data['message']);
        $this->assertEquals('INTERNAL_ERROR', $data['error_code']);
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function test_api_error_handler_handles_general_exception_in_development(): void
    {
        $this->app->instance('env', 'local');

        $request = Request::create('/api/test', 'GET');
        $request->headers->set('Accept', 'application/json');

        $middleware = new \App\Http\Middleware\ApiErrorHandler;
        $response = $middleware->handle($request, function ($req) {
            throw new \Exception('Something went wrong');
        });

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertEquals(500, $response->getStatusCode());
        $this->assertJson($response->getContent());

        $data = json_decode($response->getContent(), true);
        $this->assertFalse($data['success']);
        $this->assertEquals('Something went wrong', $data['message']);
        $this->assertEquals('INTERNAL_ERROR', $data['error_code']);
    }
}
