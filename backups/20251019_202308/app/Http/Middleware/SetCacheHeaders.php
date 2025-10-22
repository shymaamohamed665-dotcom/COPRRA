<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetCacheHeaders
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): \Symfony\Component\HttpFoundation\Response
    {
        $response = $next($request);

        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        if ($request->isMethod('GET') && $response->getStatusCode() === 200) {
            $response->headers->set('Cache-Control', 'public, max-age=3600');
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + 3600).' GMT');
        }

        return $response;
    }
}
