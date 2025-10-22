<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureEmailIsVerified
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        if (auth()->check() && ! auth()->user()?->hasVerifiedEmail()) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Email verification required'], 403);
            }

            return redirect()->route('verification.notice');
        }

        $response = $next($request);
        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }
}
