<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use App\View\Composers\AppComposer;
use Illuminate\Routing\Route;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as IlluminateView;

class ViewServiceProvider extends ServiceProvider
{
    private const BREADCRUMB_CONFIG = [
        'products.show' => [
            'parent_name' => 'Products',
            'parent_route' => 'products.index',
            'param_name' => 'product',
        ],
        'categories.show' => [
            'parent_name' => 'Categories',
            'parent_route' => 'categories.index',
            'param_name' => 'category',
        ],
        'brands.show' => [
            'parent_name' => 'Brands',
            'parent_route' => 'brands.index',
            'param_name' => 'brand',
        ],
    ];

    /**
     * Register any application services.
     */
    #[\Override]
    public function register(): void
    {
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerGlobalComposers();
        $this->registerLayoutComposers();
        $this->registerBreadcrumbComposers();
    }

    private function registerGlobalComposers(): void
    {
        View::composer('*', AppComposer::class);
    }

    private function registerLayoutComposers(): void
    {
        View::composer(['layouts.app', 'layouts.admin'], static function (IlluminateView $view): void {
            $view->with('user', auth()->user());
        });
    }

    private function registerBreadcrumbComposers(): void
    {
        View::composer(['products.*', 'categories.*', 'brands.*'], function (IlluminateView $view): void {
            $view->with('breadcrumbs', $this->getBreadcrumbs());
        });
    }

    /**
     * Get breadcrumbs for the current page.
     *
     * @return array<int, array<string, string|null>>
     */
    private function getBreadcrumbs(): array
    {
        $breadcrumbs = [
            ['name' => 'Home', 'url' => route('home')],
        ];

        $route = request()->route();

        if (! $route instanceof Route) {
            return $breadcrumbs;
        }

        $routeName = $route->getName();

        if (isset(self::BREADCRUMB_CONFIG[$routeName])) {
            $this->addConfiguredBreadcrumbs($breadcrumbs, $route, self::BREADCRUMB_CONFIG[$routeName]);
        }

        return $breadcrumbs;
    }

    /**
     * Add breadcrumbs based on the route configuration.
     *
     * @param  array<int, array<string, string|null>>  $breadcrumbs
     * @param  array<string, string>  $config
     */
    private function addConfiguredBreadcrumbs(array &$breadcrumbs, Route $route, array $config): void
    {
        $breadcrumbs[] = ['name' => $config['parent_name'], 'url' => route($config['parent_route'])];

        $param = $route->parameter($config['param_name']);

        if ($param && isset($param->name)) {
            $breadcrumbs[] = ['name' => $param->name, 'url' => null];
        }
    }
}
