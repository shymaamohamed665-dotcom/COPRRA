<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Cache\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Routing\Registrar as Router;
use Illuminate\Http\Request;

final readonly class RouteConfigurationService
{
    public function __construct(private RateLimiter $rateLimiter, private Router $router)
    {
    }

    public function configureRateLimiting(): void
    {
        // API rate limiting
        $this->rateLimiter->for('api', static fn (Request $request) => Limit::perMinute(100)->by($request->ip()));

        // Authenticated rate limiting
        $this->rateLimiter->for('authenticated', static fn (Request $request) => Limit::perMinute(200)->by($request->user() && $request->user()->id ? $request->user()->id : $request->ip()));

        // Admin rate limiting
        $this->rateLimiter->for('admin', static fn (Request $request) => Limit::perMinute(500)->by($request->user()->id ?? $request->ip()));

        // Auth rate limiting (for login attempts)
        $this->rateLimiter->for('auth', static fn (Request $request) => Limit::perMinute(10)->by($request->ip()));

        // AI rate limiting
        $this->rateLimiter->for('ai', static fn (Request $request) => Limit::perMinute(50)->by($request->user()->id ?? $request->ip()));

        // Public rate limiting
        $this->rateLimiter->for('public', static fn (Request $request) => Limit::perMinute(60)->by($request->ip()));
    }

    public function configureRoutes(): void
    {
        $this->router->group(['middleware' => 'api', 'prefix' => 'api'], static function (): void {
            require base_path('routes/api.php');
        });

        $this->router->group(['middleware' => 'web'], static function (): void {
            require base_path('routes/web.php');
        });
    }
}
