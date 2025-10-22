<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 */
class AddQueuedCookiesToResponse
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        $this->addCookiesToResponse($response);

        return $response;
    }

    /**
     * Add the queued cookies to the response.
     */
    private function addCookiesToResponse(Response $response): void
    {
        if (app()->bound('cookie.queue')) {
            $cookieQueue = app('cookie.queue');
            if (is_object($cookieQueue) && method_exists($cookieQueue, 'getQueuedCookies')) {
                $cookies = $cookieQueue->getQueuedCookies();
                if (is_iterable($cookies)) {
                    foreach ($cookies as $cookie) {
                        if ($cookie instanceof \Symfony\Component\HttpFoundation\Cookie) {
                            $response->headers->setCookie($cookie);
                        }
                    }
                }
            }
        }
    }
}
