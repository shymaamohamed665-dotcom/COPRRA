<?php

/** @psalm-suppress UnusedClass */

declare(strict_types=1);

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->app->make(\Illuminate\Contracts\Broadcasting\Factory::class)->routes();

        require_once base_path('routes/channels.php');
    }
}
