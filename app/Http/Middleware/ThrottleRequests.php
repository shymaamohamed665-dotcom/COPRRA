<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\CacheManager;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleRequests
{
    private CacheManager $cache;

    public function __construct(CacheManager $cache)
    {
        $this->cache = $cache;
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $this->handleThrottling($request);
        if ($response) {
            return $response;
        }

        $response = $next($request);
        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    private function handleThrottling(Request $request): ?Response
    {
        $key = $request->ip();
        $maxAttempts = 60;
        $decayMinutes = 1;

        if ($this->cache->has("throttle:{$key}")) {
            $attempts = $this->cache->get("throttle:{$key}", 0);
            if ($attempts >= $maxAttempts) {
                return response()->json(['message' => 'Too Many Requests'], 429)
                    ->header('Retry-After', (string) ($decayMinutes * 60));
            }
            $this->cache->put("throttle:{$key}", $attempts + 1, $decayMinutes);
        } else {
            $this->cache->put("throttle:{$key}", 1, $decayMinutes);
        }

        return null;
    }
}
