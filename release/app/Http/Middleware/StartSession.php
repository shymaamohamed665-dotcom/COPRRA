<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 */
class StartSession
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        if (! $request->hasSession()) {
            $request->setLaravelSession(
                app('session.store')
            );
        }

        $response = $next($request);
        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }
}
