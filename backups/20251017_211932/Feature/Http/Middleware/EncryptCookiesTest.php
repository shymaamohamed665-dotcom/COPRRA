<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class EncryptCookiesTest extends TestCase
{
    use RefreshDatabase;

    public function test_encrypt_cookies_middleware_encrypts_cookies(): void
    {
        $request = Request::create('/test', 'GET');

        $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
        $middleware = new \App\Http\Middleware\EncryptCookies($encrypter);
        $response = $middleware->handle($request, function ($req) {
            $response = response('OK', 200);
            $response->cookie('test_cookie', 'test_value');

            return $response;
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertTrue($response->headers->has('Set-Cookie'));

        // Verify that the cookie is actually encrypted
        $cookies = $response->headers->getCookies();
        $this->assertCount(1, $cookies);

        $cookie = $cookies[0];
        $this->assertEquals('test_cookie', $cookie->getName());

        // The cookie value should be encrypted (not equal to the original value)
        $this->assertNotEquals('test_value', $cookie->getValue());

        // Verify the cookie value is not empty and looks encrypted
        $this->assertNotEmpty($cookie->getValue());
        $this->assertGreaterThan(10, strlen($cookie->getValue()));
    }

    public function test_encrypt_cookies_middleware_passes_request_successfully(): void
    {
        $request = Request::create('/test', 'GET');

        $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
        $middleware = new \App\Http\Middleware\EncryptCookies($encrypter);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());
    }

    public function test_encrypt_cookies_middleware_handles_multiple_cookies(): void
    {
        $request = Request::create('/test', 'GET');

        $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
        $middleware = new \App\Http\Middleware\EncryptCookies($encrypter);
        $response = $middleware->handle($request, function ($req) {
            $response = response('OK', 200);
            $response->cookie('cookie1', 'value1');
            $response->cookie('cookie2', 'value2');

            return $response;
        });

        $this->assertEquals(200, $response->getStatusCode());
        $cookies = $response->headers->getCookies();
        $this->assertCount(2, $cookies);

        // Verify both cookies are encrypted
        foreach ($cookies as $cookie) {
            $this->assertNotEquals('value1', $cookie->getValue());
            $this->assertNotEquals('value2', $cookie->getValue());

            // Verify the cookie value is not empty and looks encrypted
            $this->assertNotEmpty($cookie->getValue());
            $this->assertGreaterThan(10, strlen($cookie->getValue()));
        }
    }

    public function test_encrypt_cookies_middleware_handles_existing_cookies(): void
    {
        $request = Request::create('/test', 'GET');
        $request->cookies->set('existing_cookie', 'existing_value');

        $encrypter = app(\Illuminate\Contracts\Encryption\Encrypter::class);
        $middleware = new \App\Http\Middleware\EncryptCookies($encrypter);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('OK', $response->getContent());

        // Verify that no new cookies were set in the response
        $this->assertFalse($response->headers->has('Set-Cookie'));
    }
}
