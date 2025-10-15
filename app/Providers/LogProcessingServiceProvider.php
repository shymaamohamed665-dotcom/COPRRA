<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\LogProcessing\ErrorStatisticsCalculator;
use App\Services\LogProcessing\LogFileReader;
use App\Services\LogProcessing\LogLineParser;
use App\Services\LogProcessing\LogProcessingService;
use App\Services\LogProcessing\SystemHealthChecker;
use Illuminate\Support\ServiceProvider;

class LogProcessingServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    #[\Override]
    public function register(): void
    {
        $this->app->singleton(LogFileReader::class);
        $this->app->singleton(LogLineParser::class);
        $this->app->singleton(ErrorStatisticsCalculator::class);
        $this->app->singleton(SystemHealthChecker::class);

        $this->app->singleton(LogProcessingService::class, function ($app) {
            return new LogProcessingService(
                $app->make(LogFileReader::class),
                $app->make(LogLineParser::class),
                $app->make(ErrorStatisticsCalculator::class),
                $app->make(SystemHealthChecker::class)
            );
        });
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void {}
}
