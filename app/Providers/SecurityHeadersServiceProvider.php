<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Security\Headers\SecurityHeaderStrategyFactory;
use App\Services\Security\SecurityHeaderConfiguration;
use App\Services\Security\SecurityHeadersService;
use Illuminate\Support\ServiceProvider;

class SecurityHeadersServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        // Register SecurityHeaderStrategyFactory as singleton
        $this->app->singleton(SecurityHeaderStrategyFactory::class, function () {
            return new SecurityHeaderStrategyFactory;
        });

        // Register SecurityHeaderConfiguration as singleton
        $this->app->singleton(SecurityHeaderConfiguration::class, function () {
            $configuration = new SecurityHeaderConfiguration;
            $configuration->loadFromConfig();

            return $configuration;
        });

        // Register SecurityHeadersService as singleton
        $this->app->singleton(SecurityHeadersService::class, function ($app) {
            return new SecurityHeadersService(
                $app->make(SecurityHeaderConfiguration::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration if needed
        $this->publishes([
            __DIR__.'/../../config/security.php' => config_path('security.php'),
        ], 'config');
    }
}
