<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use App\Services\RouteConfigurationService;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;

final class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to your application's "home" route.
     *
     * Typically, users are redirected here after authentication.
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    #[\Override]
    public function boot(): void
    {
        $rateLimiter = $this->app->make(\Illuminate\Cache\RateLimiter::class);
        $router = $this->app->make(\Illuminate\Contracts\Routing\Registrar::class);

        $routeConfigService = new RouteConfigurationService($rateLimiter, $router);
        $routeConfigService->configureRateLimiting();

        $this->routes(static function () use ($routeConfigService): void {
            $routeConfigService->configureRoutes();
        });
    }
}
