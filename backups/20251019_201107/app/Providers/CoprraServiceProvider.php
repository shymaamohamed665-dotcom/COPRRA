<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

final class CoprraServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        // Merge COPRRA configuration
        $this->mergeConfigFrom(
            config_path('coprra.php'),
            'coprra'
        );
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->registerBladeDirectives();

        // Share COPRRA configuration with views
        View::share('coprraName', config('coprra.name'));
        View::share('coprraVersion', config('coprra.version'));
        View::share('defaultCurrency', config('coprra.default_currency'));
        View::share('defaultLanguage', config('coprra.default_language'));
    }

    /**
     * Register custom Blade directives.
     */
    private function registerBladeDirectives(): void
    {
        // Currency formatting directive
        Blade::directive('currency', static function ($expression): string {
            $expressionStr = is_string($expression) ? $expression : '';

            return "<?php echo number_format({$expressionStr}, 2); ?>";
        });

        // Price comparison directive
        Blade::directive('pricecompare', static function ($expression): string {
            $expressionStr = is_string($expression) ? $expression : '';

            return "<?php echo App\\Helpers\\PriceHelper::formatPrice({$expressionStr}); ?>";
        });

        // Language direction directive
        Blade::directive('rtl', static fn (): string => "<?php echo in_array(app()->getLocale(), ['ar', 'ur', 'fa']) ? 'rtl' : 'ltr'; ?>");
    }
}
