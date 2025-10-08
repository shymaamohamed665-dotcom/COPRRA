<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeaders
{
    /**
     * @var array<string, string>
     */
    private array $securityHeaders = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
        'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'X-Permitted-Cross-Domain-Policies' => 'none',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $this->ensureSecureRequest($request);

        $response = $next($request);

        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        $this->addSecurityHeaders($response);
        $this->preventClickjacking($request, $response);
        $this->logSuspiciousRequest($request);

        return $response;
    }

    private function ensureSecureRequest(Request $request): void
    {
        if (app()->environment('production') && ! $request->secure()) {
            redirect()->secure($request->getRequestUri())->send();
        }
    }

    private function addSecurityHeaders(Response $response): void
    {
        foreach ($this->securityHeaders as $key => $value) {
            $response->headers->set($key, $value);
        }
    }

    private function preventClickjacking(Request $request, Response $response): void
    {
        if ($this->isSensitiveRoute($request)) {
            $response->headers->set('X-Frame-Options', 'DENY');
        }
    }

    private function logSuspiciousRequest(Request $request): void
    {
        if ($this->isSuspiciousRequest($request)) {
            Log::warning('Suspicious request detected', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'uri' => $request->getRequestUri(),
                'payload' => $request->except(['password', 'password_confirmation']),
            ]);
        }
    }

    private function isSensitiveRoute(Request $request): bool
    {
        $sensitivePaths = ['admin/*', 'settings/*', 'profile/*', 'billing/*', 'api/v1/admin/*'];

        foreach ($sensitivePaths as $path) {
            if ($request->is($path)) {
                return true;
            }
        }

        return false;
    }

    private function isSuspiciousRequest(Request $request): bool
    {
        $input = strtolower(json_encode($request->all()) ?? '');

        return $this->containsSqlInjection($input)
            || $this->containsXss($input)
            || $this->hasSuspiciousFileUploads($request);
    }

    private function containsSqlInjection(string $input): bool
    {
        $patterns = [
            'union select',
            'union all select',
            'from information_schema',
            'exec(',
            'eval(',
            ';',
            '-- ',
            '/*',
            '*/',
            'xp_cmdshell',
            'drop table',
            'drop database',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($input, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function containsXss(string $input): bool
    {
        $patterns = [
            '<script',
            'javascript:',
            'onerror=',
            'onload=',
            'onclick=',
            'onmouseover=',
        ];

        foreach ($patterns as $pattern) {
            if (str_contains($input, $pattern)) {
                return true;
            }
        }

        return false;
    }

    private function hasSuspiciousFileUploads(Request $request): bool
    {
        $files = $request->allFiles();
        if (! $files) {
            return false;
        }

        $suspiciousExtensions = ['.php', '.phtml', '.phar', '.htaccess', '.env'];
        $allFiles = [];
        array_walk_recursive($files, static function ($file) use (&$allFiles): void {
            $allFiles[] = $file;
        });

        foreach ($allFiles as $file) {
            if ($file instanceof UploadedFile) {
                $fileName = strtolower($file->getClientOriginalName());
                foreach ($suspiciousExtensions as $extension) {
                    if (str_ends_with($fileName, $extension)) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
}
