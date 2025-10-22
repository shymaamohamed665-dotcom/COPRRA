<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 */
class Authorize
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $ability): \Symfony\Component\HttpFoundation\Response
    {
        if (auth()->check() && auth()->user()?->can($ability)) {
            $response = $next($request);
            if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
                throw new \RuntimeException('Middleware must return Response instance');
            }

            return $response;
        }

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        return response('Forbidden', 403);
    }
}
