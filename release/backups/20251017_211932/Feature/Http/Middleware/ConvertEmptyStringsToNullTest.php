<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class ConvertEmptyStringsToNullTest extends TestCase
{
    use RefreshDatabase;

    public function test_convert_empty_strings_middleware_converts_empty_strings_to_null(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => 'test@example.com',
            'description' => '',
            'age' => 25,
        ]);

        $middleware = new \App\Http\Middleware\ConvertEmptyStringsToNull;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($request->input('name'));
        $this->assertEquals('test@example.com', $request->input('email'));
        $this->assertNull($request->input('description'));
        $this->assertEquals(25, $request->input('age'));
    }

    public function test_convert_empty_strings_middleware_handles_nested_arrays(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '',
                'email' => 'test@example.com',
            ],
            'address' => [
                'street' => '',
                'city' => 'New York',
            ],
        ]);

        $middleware = new \App\Http\Middleware\ConvertEmptyStringsToNull;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertNull($request->input('user.name'));
        $this->assertEquals('test@example.com', $request->input('user.email'));
        $this->assertNull($request->input('address.street'));
        $this->assertEquals('New York', $request->input('address.city'));
    }

    public function test_convert_empty_strings_middleware_does_not_convert_non_string_values(): void
    {
        $request = Request::create('/test', 'POST', [
            'age' => 0,
            'is_active' => false,
            'tags' => [],
            'name' => '',
        ]);

        $middleware = new \App\Http\Middleware\ConvertEmptyStringsToNull;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(0, $request->input('age'));
        $this->assertFalse($request->input('is_active'));
        $this->assertEquals([], $request->input('tags'));
        $this->assertNull($request->input('name'));
    }

    public function test_convert_empty_strings_middleware_passes_request_successfully(): void
    {
        $request = Request::create('/test', 'GET');

        $middleware = new \App\Http\Middleware\ConvertEmptyStringsToNull;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
