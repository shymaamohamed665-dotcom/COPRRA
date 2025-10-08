<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SubstituteBindings
{
    public function handle(Request $request, Closure $next): Response
    {
        $this->substituteBindings($request);

        $response = $next($request);
        if (! ($response instanceof Response)) {
            throw new \RuntimeException('Middleware must return Response instance');
        }

        return $response;
    }

    private function substituteBindings(Request $request): void
    {
        $route = $request->route();

        if (! $route) {
            return;
        }

        foreach ($route->parameters() as $key => $value) {
            $this->convertNumericParameter($route, $key, $value);
        }
    }

    private function convertNumericParameter(\Illuminate\Routing\Route $route, string $key, string|int|null $value): void
    {
        if (is_string($value) && is_numeric($value)) {
            $route->setParameter($key, +$value);
        }
    }
}
