<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureResponseHasSession
{
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $session = $request->session();

        if (method_exists($response, 'setSession') && $session) {
            // Attach the current session store to the response so tests can read it
            // via $response->session() assertions.
            $response->setSession($session);
        }

        return $response;
    }
}
