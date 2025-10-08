<?php

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class TrimStringsTest extends TestCase
{
    public function test_trim_strings_middleware_trims_string_input(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '  John Doe  ',
            'email' => '  john@example.com  ',
            'description' => '  This is a test description  ',
        ]);

        $middleware = new \App\Http\Middleware\TrimStrings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('John Doe', $request->input('name'));
        $this->assertEquals('john@example.com', $request->input('email'));
        $this->assertEquals('This is a test description', $request->input('description'));
    }

    public function test_trim_strings_middleware_does_not_trim_non_string_input(): void
    {
        $request = Request::create('/test', 'POST', [
            'age' => 25,
            'is_active' => true,
            'tags' => ['tag1', 'tag2', 'tag3'],
        ]);

        $middleware = new \App\Http\Middleware\TrimStrings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals(25, $request->input('age'));
        $this->assertTrue($request->input('is_active'));
        $this->assertEquals(['tag1', 'tag2', 'tag3'], $request->input('tags'));
    }

    public function test_trim_strings_middleware_handles_nested_arrays(): void
    {
        $request = Request::create('/test', 'POST', [
            'user' => [
                'name' => '  John Doe  ',
                'email' => '  john@example.com  ',
            ],
            'address' => [
                'street' => '  123 Main St  ',
                'city' => '  New York  ',
            ],
        ]);

        $middleware = new \App\Http\Middleware\TrimStrings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('John Doe', $request->input('user.name'));
        $this->assertEquals('john@example.com', $request->input('user.email'));
        $this->assertEquals('123 Main St', $request->input('address.street'));
        $this->assertEquals('New York', $request->input('address.city'));
    }

    public function test_trim_strings_middleware_handles_empty_strings(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '',
            'email' => '   ',
            'description' => null,
        ]);

        $middleware = new \App\Http\Middleware\TrimStrings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('', $request->input('name'));
        $this->assertEquals('', $request->input('email'));
        $this->assertNull($request->input('description'));
    }

    public function test_trim_strings_middleware_passes_request_to_next_middleware(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => '  John Doe  ',
        ]);

        $middleware = new \App\Http\Middleware\TrimStrings;
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }
}
