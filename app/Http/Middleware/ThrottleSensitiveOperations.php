<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;

class ThrottleSensitiveOperations
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next, string $operation = 'default'): \Symfony\Component\HttpFoundation\Response
    {
        $key = $this->resolveRequestSignature($request, $operation);

        // Define rate limits for different operations
        $limits = $this->getRateLimits($operation);
        $maxAttempts = $limits['max_attempts'];
        $decaySeconds = $limits['decay_seconds'];

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            $seconds = RateLimiter::availableIn($key);

            Log::warning('Rate limit exceeded', [
                'operation' => $operation,
                'ip' => $request->ip(),
                'user_id' => $request->user()?->id,
                'seconds_remaining' => $seconds,
            ]);

            return response()->json([
                'message' => 'Too many attempts. Please try again in '.$seconds.' seconds.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($key, $decaySeconds);

        $response = $next($request);
        if (! ($response instanceof \Symfony\Component\HttpFoundation\Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    /**
     * Resolve the request signature for rate limiting.
     */
    protected function resolveRequestSignature(Request $request, string $operation): string
    {
        $user = $request->user();
        $ip = $request->ip();

        if ($user) {
            return "throttle:{$operation}:user:{$user->id}";
        }

        return "throttle:{$operation}:ip:{$ip}";
    }

    /**
     * Get rate limits for different operations.
     *
     * @return array{max_attempts: int, decay_seconds: int}
     */
    protected function getRateLimits(string $operation): array
    {
        $limits = [
            'login' => ['max_attempts' => 5, 'decay_seconds' => 300], // 5 attempts per 5 minutes
            'register' => ['max_attempts' => 3, 'decay_seconds' => 600], // 3 attempts per 10 minutes
            'password_reset' => ['max_attempts' => 3, 'decay_seconds' => 600], // 3 attempts per 10 minutes
            'price_alert' => ['max_attempts' => 10, 'decay_seconds' => 60], // 10 attempts per minute
            'wishlist' => ['max_attempts' => 20, 'decay_seconds' => 60], // 20 attempts per minute
            'review' => ['max_attempts' => 5, 'decay_seconds' => 300], // 5 attempts per 5 minutes
            'search' => ['max_attempts' => 100, 'decay_seconds' => 60], // 100 attempts per minute
            'api' => ['max_attempts' => 1000, 'decay_seconds' => 60], // 1000 attempts per minute
            'admin' => ['max_attempts' => 200, 'decay_seconds' => 60], // 200 attempts per minute
            'default' => ['max_attempts' => 60, 'decay_seconds' => 60], // 60 attempts per minute
        ];

        return $limits[$operation] ?? $limits['default'];
    }
}
