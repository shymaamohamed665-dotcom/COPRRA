<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Api\ApiInfoService;
use App\Services\Api\PaginationService;
use App\Services\Api\RequestParameterService;
use App\Services\Api\ResponseBuilderService;
use Illuminate\Support\ServiceProvider;

/**
 * Service provider for API services
 */
class ApiServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        $this->app->singleton(ResponseBuilderService::class);
        $this->app->singleton(PaginationService::class);
        $this->app->singleton(RequestParameterService::class);
        $this->app->singleton(ApiInfoService::class);
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
