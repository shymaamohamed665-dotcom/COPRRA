<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateWithBasicAuth
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->isAuthorized($request->getUser(), $request->getPassword())) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="API"',
            ]);
        }

        $response = $next($request);
        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    /**
     * Check if the provided credentials are valid.
     */
    private function isAuthorized(?string $username, ?string $password): bool
    {
        if (! $username || ! $password) {
            return false;
        }

        return $username === config('app.basic_auth_username') &&
            $password === config('app.basic_auth_password');
    }
}
