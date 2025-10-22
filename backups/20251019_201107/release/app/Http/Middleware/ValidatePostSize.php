<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidatePostSize
{
    private const MAX_SIZE = 8 * 1024 * 1024; // 8MB

    public function handle(Request $request, Closure $next): Response
    {
        if ($this->isModifyingRequest($request) && $this->isPostSizeExceeded($request)) {
            return response()->json(['message' => 'Request entity too large'], 413);
        }

        return $next($request);
    }

    private function isModifyingRequest(Request $request): bool
    {
        return in_array($request->getMethod(), ['POST', 'PUT', 'PATCH'], true);
    }

    private function isPostSizeExceeded(Request $request): bool
    {
        return (int) $request->header('Content-Length', '0') > self::MAX_SIZE;
    }
}
