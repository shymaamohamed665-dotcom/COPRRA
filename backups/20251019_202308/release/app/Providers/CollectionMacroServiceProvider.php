<?php

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

class CollectionMacroServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        Collection::macro('penultimate', function () {
            if ($this->count() < 2) {
                return null;
            }

            return $this->slice(-2, 1)->first();
        });
    }
}
