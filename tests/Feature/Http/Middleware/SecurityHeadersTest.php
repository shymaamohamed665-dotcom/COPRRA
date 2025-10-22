<?php

declare(strict_types=1);

namespace Tests\Feature\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class SecurityHeadersTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Allow debug logs without explicit expectations
        Log::shouldReceive('debug')->zeroOrMoreTimes();
    }

    public function test_security_headers_middleware_adds_security_headers(): void
    {
        $request = Request::create('/test', 'GET');

        // Ø§Ø³ØªØ®Ø¯Ù… Ø§Ù„Ø­Ø§ÙˆÙŠØ© Ù„Ø¥Ù†Ø´Ø§Ø¡ Ø§Ù„ÙˆØ³ÙŠØ· Ù„ÙŠØªÙ… Ø­Ù‚Ù† Ø§Ù„Ø®Ø¯Ù…Ø© ØªÙ„Ù‚Ø§Ø¦ÙŠÙ‹Ø§
        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('SAMEORIGIN', $response->headers->get('X-Frame-Options'));
        $this->assertEquals('1; mode=block', $response->headers->get('X-XSS-Protection'));
        $this->assertEquals('nosniff', $response->headers->get('X-Content-Type-Options'));
        $this->assertEquals('strict-origin-when-cross-origin', $response->headers->get('Referrer-Policy'));
        $this->assertStringContainsString("default-src 'self'", $response->headers->get('Content-Security-Policy'));
        $this->assertStringContainsString('max-age=31536000', $response->headers->get('Strict-Transport-Security'));
        $this->assertEquals('camera=(), microphone=(), geolocation=()', $response->headers->get('Permissions-Policy'));
        $this->assertEquals('none', $response->headers->get('X-Permitted-Cross-Domain-Policies'));
    }

    public function test_security_headers_middleware_handles_sensitive_routes(): void
    {
        $request = Request::create('/admin/dashboard', 'GET');

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_middleware_handles_settings_routes(): void
    {
        $request = Request::create('/settings/profile', 'GET');

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_middleware_handles_profile_routes(): void
    {
        $request = Request::create('/profile/edit', 'GET');

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_middleware_handles_billing_routes(): void
    {
        $request = Request::create('/billing/payment', 'GET');

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_middleware_handles_admin_api_routes(): void
    {
        $request = Request::create('/api/v1/admin/users', 'GET');

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('DENY', $response->headers->get('X-Frame-Options'));
    }

    public function test_security_headers_middleware_logs_suspicious_sql_injection_attempts(): void
    {
        Log::shouldReceive('warning')->once()->with('Suspicious request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'POST', [
            'query' => 'SELECT * FROM users WHERE id = 1; DROP TABLE users; --',
        ]);

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_security_headers_middleware_logs_suspicious_xss_attempts(): void
    {
        Log::shouldReceive('warning')->once()->with('Suspicious request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'POST', [
            'comment' => '<script>alert("XSS")</script>',
        ]);

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_security_headers_middleware_logs_suspicious_file_uploads(): void
    {
        Log::shouldReceive('warning')->once()->with('Suspicious request detected', \Mockery::type('array'));

        $request = Request::create('/test', 'POST');

        // Create a temporary test file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_');
        file_put_contents($tempFile, '<?php echo "test"; ?>');

        // Mock file upload
        $file = new \Illuminate\Http\UploadedFile(
            $tempFile,
            'test.php',
            'application/x-php',
            null,
            true
        );

        $request->files->set('upload', $file);

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_security_headers_middleware_does_not_log_normal_requests(): void
    {
        Log::shouldReceive('warning')->never();

        $request = Request::create('/test', 'POST', [
            'name' => 'John Doe',
            'email' => 'john@example.com',
        ]);

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }

    public function test_security_headers_middleware_handles_https_redirect_in_production(): void
    {
        $this->app->instance('env', 'production');

        $request = Request::create('http://example.com/test', 'GET');
        $request->server->set('HTTPS', false);

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(302, $response->getStatusCode());
        $this->assertStringContainsString('https://', $response->headers->get('Location'));
    }

    public function test_security_headers_middleware_does_not_redirect_https_in_development(): void
    {
        $this->app->instance('env', 'local');

        $request = Request::create('http://example.com/test', 'GET');
        $request->server->set('HTTPS', false);

        $middleware = $this->app->make(\App\Http\Middleware\SecurityHeaders::class);
        $response = $middleware->handle($request, function ($req) {
            return response('OK', 200);
        });

        $this->assertEquals(200, $response->getStatusCode());
    }
}
