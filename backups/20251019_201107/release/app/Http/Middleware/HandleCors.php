<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Middleware\HandleCors as Middleware;

class HandleCors extends Middleware
{
    /**
     * Allow direct instantiation in tests without framework DI.
     */
    // Use parent constructor when resolved by Laravel's container.
    // Avoid overriding the parent signature to keep DI compatibility.

    /**
     * Handle an incoming request and append CORS headers.
     */
    #[\Override]
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        try {
            // Support both Illuminate and Symfony request types
            $origin = method_exists($request, 'headers')
                ? (string) ($request->headers->get('Origin') ?? '*')
                : (string) ($request->headers->get('Origin') ?? '*');

            $reqMethod = method_exists($request, 'headers')
                ? (string) ($request->headers->get('Access-Control-Request-Method') ?? 'GET, POST, PUT, DELETE, OPTIONS')
                : 'GET, POST, PUT, DELETE, OPTIONS';

            $reqHeaders = method_exists($request, 'headers')
                ? (string) ($request->headers->get('Access-Control-Request-Headers') ?? 'Content-Type, Authorization, X-Requested-With')
                : 'Content-Type, Authorization, X-Requested-With';

            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', $reqMethod);
            $response->headers->set('Access-Control-Allow-Headers', $reqHeaders);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } catch (\Throwable $e) {
            // Fail-safe: do not break the response pipeline in tests
        }

        return $response;
    }
}
