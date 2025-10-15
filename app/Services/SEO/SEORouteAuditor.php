<?php

declare(strict_types=1);

namespace App\Services\SEO;

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Route;

final class SEORouteAuditor
{
    /**
     * Get all public-facing GET routes.
     *
     * @return string[]
     *
     * @psalm-return list<string>
     */
    public function getPublicRoutes(): array
    {
        $routes = Route::getRoutes();
        $publicRoutes = [];

        foreach ($routes->getRoutes() as $route) {
            if ($this->isValidPublicRoute($route)) {
                $uri = $route->uri();
                if (is_string($uri)) {
                    $publicRoutes[] = $uri;
                }
            }
        }

        return $publicRoutes;
    }

    /**
     * Check for duplicate routes.
     *
     * @param  array<string>  $routes
     * @return int[]
     *
     * @psalm-return array<string, int<2, max>>
     */
    public function findDuplicateRoutes(array $routes): array
    {
        $routeCounts = array_count_values($routes);

        return array_filter($routeCounts, fn ($count) => $count > 1);
    }

    /**
     * Check if a route is a valid public route.
     */
    private function isValidPublicRoute(IlluminateRoute $route): bool
    {
        if (! $this->isGetRoute($route)) {
            return false;
        }

        return ! $this->isExcludedRoute($route);
    }

    /**
     * Check if route is a GET route.
     */
    private function isGetRoute(IlluminateRoute $route): bool
    {
        return in_array('GET', $route->methods(), true);
    }

    /**
     * Check if route should be excluded from audit.
     */
    private function isExcludedRoute(IlluminateRoute $route): bool
    {
        $uri = $route->uri();
        if (! is_string($uri)) {
            return true;
        }

        return $this->isApiRoute($uri) || $this->isAdminRoute($uri);
    }

    /**
     * Check if route is an API route.
     */
    private function isApiRoute(string $uri): bool
    {
        return str_starts_with($uri, 'api/');
    }

    /**
     * Check if route is an admin route.
     */
    private function isAdminRoute(string $uri): bool
    {
        return str_starts_with($uri, 'admin/');
    }
}
