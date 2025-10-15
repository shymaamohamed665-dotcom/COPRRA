<?php

declare(strict_types=1);

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        // Use a non-conflicting health endpoint to avoid overriding /api/health JSON route
        health: '/healthz',
    )
    ->withCommands([
        \App\Console\Commands\StatsCommand::class,
        \App\Console\Commands\UpdatePricesCommand::class,
    ])
    ->withMiddleware(function (Middleware $middleware) {
        // Global middleware - applied to all requests
        // Ensure CSP nonce is generated before applying security headers
        // Override health endpoint to ensure JSON at /api/health
        $middleware->append(\App\Http\Middleware\OverrideHealthEndpoint::class);
        $middleware->append(\App\Http\Middleware\AddCspNonce::class);
        $middleware->append(\App\Http\Middleware\SecurityHeadersMiddleware::class);
        // Ensure sessions and error sharing are always available via the web group only
        // (Avoid duplicating session middleware globally which can cause inconsistencies)

        // Web middleware group - include session start and error sharing
        $middleware->web([
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\LocaleMiddleware::class,
            \App\Http\Middleware\SetLocaleAndCurrency::class,
        ]);

        // API middleware group
        $middleware->api([
            \Laravel\Sanctum\Http\Middleware\EnsureFrontendRequestsAreStateful::class,
            'throttle:api',
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
            \App\Http\Middleware\ApiErrorHandler::class,
        ]);

        // Middleware aliases
        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'locale' => \App\Http\Middleware\LocaleMiddleware::class,
            'role' => \App\Http\Middleware\CheckUserRole::class,
            'permission' => \App\Http\Middleware\CheckPermission::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {})->create();
