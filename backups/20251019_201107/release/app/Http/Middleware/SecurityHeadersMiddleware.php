<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Services\Security\SecurityHeadersService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SecurityHeadersMiddleware
{
    private readonly SecurityHeadersService $securityHeadersService;

    public function __construct(SecurityHeadersService $securityHeadersService)
    {
        $this->securityHeadersService = $securityHeadersService;
    }

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        $this->securityHeadersService->applySecurityHeaders($response, $request);

        return $response;
    }
}
