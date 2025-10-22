<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\Compression\CompressionResponseService;
use App\Services\Compression\CompressionService;
use App\Services\Compression\ContentTypeService;
use Illuminate\Support\ServiceProvider;

class CompressionServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(ContentTypeService::class);

        $this->app->singleton(CompressionService::class);

        $this->app->singleton(CompressionResponseService::class, function ($app): \App\Services\Compression\CompressionResponseService {
            return new CompressionResponseService(
                $app->make(CompressionService::class),
                $app->make(ContentTypeService::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
