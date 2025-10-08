<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            $response = $this->validateSession($request);
            if ($response) {
                return $response;
            }
        }

        $response = $next($request);
        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    /**
     * Validate the session and log out if necessary.
     */
    private function validateSession(Request $request): ?Response
    {
        $user = auth()->user();
        $sessionId = $request->session()->getId();

        if ($user?->session_id && $user->session_id !== $sessionId) {
            auth()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Session expired'], 401);
            }

            return redirect()->route('login');
        }

        return null;
    }
}
