<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use App\Models\Product;
use App\Observers\ProductObserver;
use App\Repositories\ProductRepository;
use App\Services\CacheService;
use App\Services\Contracts\CacheServiceContract;
use App\Services\PriceSearchService;
use App\Services\ProductService;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

final class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(PriceSearchService::class, static function (): \App\Services\PriceSearchService {
            return new PriceSearchService;
        });

        // Register ProductService and its dependencies
        $this->app->singleton(CacheService::class);
        // Bind CacheServiceContract to concrete CacheService
        $this->app->singleton(CacheServiceContract::class, CacheService::class);
        $this->app->singleton(ProductRepository::class);
        $this->app->singleton(ProductService::class, static function (Application $app): \App\Services\ProductService {
            $repository = $app->make(ProductRepository::class);
            if (! ($repository instanceof ProductRepository)) {
                throw new RuntimeException('Failed to resolve ProductRepository');
            }
            $cache = $app->make(CacheServiceContract::class);
            if (! ($cache instanceof CacheServiceContract)) {
                throw new RuntimeException('Failed to resolve CacheServiceContract');
            }

            return new ProductService($repository, $cache);
        });

        if ($this->app->environment('local', 'testing') && class_exists(\Laravel\Dusk\DuskServiceProvider::class)) {
            $this->app->register(\Laravel\Dusk\DuskServiceProvider::class);
        }
    }

    /**
     * Bootstrap application services.
     */
    public function boot(): void
    {
        // Invalidate related caches on product changes
        Product::observe(ProductObserver::class);
        // لا تقم بإنشاء جداول يدويًا أثناء الاختبار؛ يجب الاعتماد على الميجريشن وRefreshDatabase
    }
}
