<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use App\Repositories\ProductRepository;
use App\Services\CacheService;
use App\Services\PriceSearchService;
use App\Services\ProductService;
use Illuminate\Support\ServiceProvider;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(PriceSearchService::class, static function () {
            return new PriceSearchService;
        });

        // Register ProductService and its dependencies
        $this->app->singleton(CacheService::class);
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(ProductService::class, static function (\Illuminate\Contracts\Foundation\Application $app) {
            $repository = $app->make(ProductRepository::class);
            if (! ($repository instanceof ProductRepository)) {
                throw new \RuntimeException('Failed to resolve ProductRepository');
            }
            $cache = $app->make(CacheService::class);
            if (! ($cache instanceof CacheService)) {
                throw new \RuntimeException('Failed to resolve CacheService');
            }

            return new ProductService($repository, $cache);
        });
    }

    // ... existing code ...
}
