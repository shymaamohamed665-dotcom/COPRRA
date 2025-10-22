<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class AuthenticateWithBasicAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticate_with_basic_auth_middleware_handles_requests(): void
    {
        // Ø¥Ù†Ø´Ø§Ø¡ Ù…Ø³ØªØ®Ø¯Ù… Ø¨Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ø¹ØªÙ…Ø§Ø¯ ØµØ­ÙŠØ­Ø©
        $user = User::factory()->create([
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $request = Request::create('/test', 'GET');
        // Ø§Ø¶Ø¨Ø· Ø¨ÙŠØ§Ù†Ø§Øª Ø§Ø¹ØªÙ…Ø§Ø¯ Basic Auth Ø¹Ø¨Ø± ØªØ±ÙˆÙŠØ³Ø§Øª PHP_AUTH_*
        $request->headers->set('PHP_AUTH_USER', 'test@example.com');
        $request->headers->set('PHP_AUTH_PW', 'password123');

        // Ø§Ø±Ø¨Ø· Ø§Ù„Ø·Ù„Ø¨ Ø¯Ø§Ø®Ù„ Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„ÙŠØ³ØªØ®Ø¯Ù…Ù‡ Ø§Ù„Ø­Ø§Ø±Ø³ Ø£Ø«Ù†Ø§Ø¡ basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        // ÙŠØ¹ØªÙ…Ø¯ Ù†Ø¬Ø§Ø­ Ø§Ù„ØªÙˆØ«ÙŠÙ‚ Ø¹Ù„Ù‰ ÙˆØ¬ÙˆØ¯ Ø§Ù„Ù…Ø³ØªØ®Ø¯Ù… ÙˆØ¨ÙŠØ§Ù†Ø§Øª Ø§Ù„Ø§Ø¹ØªÙ…Ø§Ø¯ Ø§Ù„ØµØ­ÙŠØ­Ø©
        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_authenticate_with_basic_auth_middleware_handles_invalid_credentials(): void
    {
        $request = Request::create('/test', 'GET');
        $request->headers->set('PHP_AUTH_USER', 'test@example.com');
        $request->headers->set('PHP_AUTH_PW', 'wrongpassword');

        // Ø§Ø±Ø¨Ø· Ø§Ù„Ø·Ù„Ø¨ Ø¯Ø§Ø®Ù„ Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„ÙŠØ³ØªØ®Ø¯Ù…Ù‡ Ø§Ù„Ø­Ø§Ø±Ø³ Ø£Ø«Ù†Ø§Ø¡ basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_with_basic_auth_middleware_handles_missing_authorization(): void
    {
        $request = Request::create('/test', 'GET');

        // Ø§Ø±Ø¨Ø· Ø§Ù„Ø·Ù„Ø¨ Ø¯Ø§Ø®Ù„ Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„ÙŠØ³ØªØ®Ø¯Ù…Ù‡ Ø§Ù„Ø­Ø§Ø±Ø³ Ø£Ø«Ù†Ø§Ø¡ basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_with_basic_auth_middleware_handles_malformed_authorization(): void
    {
        $request = Request::create('/test', 'GET');
        // Ø¥Ø¹Ø¯Ø§Ø¯ ØªØ±ÙˆÙŠØ³Ø© ØºÙŠØ± ØµØ­ÙŠØ­Ø© (Ù„Ù† ØªÙÙ‚Ø±Ø£ ØºØ§Ù„Ø¨Ù‹Ø§ Ø¨ÙˆØ§Ø³Ø·Ø© Ø§Ù„Ø­Ø§Ø±Ø³ØŒ ÙˆØ³ÙŠÙØ´Ù„ Ø§Ù„ØªÙˆØ«ÙŠÙ‚)
        $request->headers->set('Authorization', 'InvalidFormat');

        // Ø§Ø±Ø¨Ø· Ø§Ù„Ø·Ù„Ø¨ Ø¯Ø§Ø®Ù„ Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„ÙŠØ³ØªØ®Ø¯Ù…Ù‡ Ø§Ù„Ø­Ø§Ø±Ø³ Ø£Ø«Ù†Ø§Ø¡ basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);
        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }

    public function test_authenticate_with_basic_auth_middleware_handles_post_requests(): void
    {
        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
        ]);
        $request->headers->set('PHP_AUTH_USER', 'test@example.com');
        $request->headers->set('PHP_AUTH_PW', 'password123');

        // Ø§Ø±Ø¨Ø· Ø§Ù„Ø·Ù„Ø¨ Ø¯Ø§Ø®Ù„ Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„ÙŠØ³ØªØ®Ø¯Ù…Ù‡ Ø§Ù„Ø­Ø§Ø±Ø³ Ø£Ø«Ù†Ø§Ø¡ basic auth
        $this->app->instance('request', $request);

        $middleware = $this->app->make(\App\Http\Middleware\AuthenticateWithBasicAuth::class);
        $this->expectException(\Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException::class);

        $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });
    }
}
