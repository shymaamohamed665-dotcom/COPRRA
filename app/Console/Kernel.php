<?php

declare(strict_types=1);

namespace App\Console;

use App\Jobs\FetchDailyPriceUpdates;
use App\Jobs\SendPriceAlert;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use Psr\Log\LoggerInterface;

class Kernel extends ConsoleKernel
{
    /**
     * The bootstrap classes for the application.
     *
     * Exclude HandleExceptions during unit tests to avoid overriding
     * PHPUnit's error/exception handlers.
     *
     * @var array<int, class-string>
     */
    /**
     * Override bootstrappers to skip HandleExceptions during tests.
     */
    #[\Override]
    protected function bootstrappers(): array
    {
        if ($this->app && $this->app->environment('testing')) {
            return [
                \Illuminate\Foundation\Bootstrap\LoadEnvironmentVariables::class,
                \Illuminate\Foundation\Bootstrap\LoadConfiguration::class,
                // Intentionally exclude HandleExceptions to avoid PHPUnit handler conflicts
                \Illuminate\Foundation\Bootstrap\RegisterFacades::class,
                \Illuminate\Foundation\Bootstrap\SetRequestForConsole::class,
                \Illuminate\Foundation\Bootstrap\RegisterProviders::class,
                \Illuminate\Foundation\Bootstrap\BootProviders::class,
            ];
        }

        return parent::bootstrappers();
    }

    /**
     * Define the application's command schedule.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    protected function schedule(Schedule $schedule): void
    {
        // Ensure we have a PSR logger without using static facades
        /** @var LoggerInterface $logger */
        $logger = $this->app->make(LoggerInterface::class);

        $this->scheduleSystemMaintenance($schedule, $logger);
        $this->scheduleBackups($schedule, $logger);
        $this->scheduleCopRraTasks($schedule, $logger);
        $this->schedulePriceJobs($schedule, $logger);
    }

    /**
     * Register the commands for the application.
     */
    #[\Override]
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require_once base_path('routes/console.php');
    }

    private function scheduleSystemMaintenance(Schedule $schedule, LoggerInterface $logger): void
    {
        // Queue and Cache Management
        $schedule->command('queue:prune-batches')->daily();
        $schedule->command('queue:prune-failed')->weekly();
        $schedule->command('cache:prune-stale-tags')->daily();

        // Log Management (Hostinger has file size limits)
        $schedule->command('log:prune')->daily()
            ->onSuccess(function (): void {
                // Using PSR logger via container to avoid static access
                $this->app->make(LoggerInterface::class)->info('Log pruning completed successfully');
            });

        // Session Cleanup
        $schedule->command('session:gc')->weekly();

        // Storage Cleanup (remove old temporary files)
        $schedule->command('storage:prune-temporary')->daily();

        // Database Maintenance
        $schedule->command('db:monitor')->hourly()
            ->onFailure(function () use ($logger): void {
                $logger->error('Database monitoring failed');
            });

        // Deployment Health Checks
        $schedule->command('deployment:check')->daily()
            ->onFailure(function () use ($logger): void {
                $logger->error('Deployment health check failed');
            });
    }

    private function scheduleBackups(Schedule $schedule, LoggerInterface $logger): void
    {
        // Backups (Spatie) — gated by config('backup.enabled')
        if (config('backup.enabled', true)) {
            // Run full backup daily at 2 AM
            $schedule->command('backup:run')->dailyAt('02:00')
                ->onFailure(function () use ($logger): void {
                    $logger->error('Backup run failed');
                });

            // Cleanup old backups daily at 3 AM
            $schedule->command('backup:clean')->dailyAt('03:00')
                ->onFailure(function () use ($logger): void {
                    $logger->error('Backup cleanup failed');
                });

            // Monitor backup health daily at 4 AM
            $schedule->command('backup:monitor')->dailyAt('04:00')
                ->onFailure(function () use ($logger): void {
                    $logger->error('Backup monitoring failed');
                });
        }
    }

    private function scheduleCopRraTasks(Schedule $schedule, LoggerInterface $logger): void
    {
        // COPRRA: Exchange Rates Update (Daily at 2 AM)
        $schedule->command('exchange-rates:update')->dailyAt('02:00')
            ->onSuccess(function () use ($logger): void {
                $logger->info('Exchange rates updated successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('Exchange rates update failed');
            });

        // COPRRA: Sitemap Generation (Daily at 3 AM)
        $schedule->command('sitemap:generate')->dailyAt('03:00')
            ->onSuccess(function () use ($logger): void {
                $logger->info('Sitemap generated successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('Sitemap generation failed');
            });

        // COPRRA: SEO Audit (Weekly on Sunday at 4 AM)
        $schedule->command('seo:audit --fix')->weekly()->sundays()->at('04:00')
            ->onSuccess(function () use ($logger): void {
                $logger->info('SEO audit completed successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('SEO audit failed');
            });

        // COPRRA: Analytics Cleanup (Monthly on 1st at 5 AM)
        $schedule->command('analytics:clean --days=90 --force')->monthly()->at('05:00')
            ->onSuccess(function () use ($logger): void {
                $logger->info('Analytics cleanup completed successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('Analytics cleanup failed');
            });

        // COPRRA: Process Pending Webhooks (Every 5 minutes)
        $schedule->command('webhooks:process --limit=100')->everyFiveMinutes()
            ->onSuccess(function () use ($logger): void {
                $logger->info('Webhooks processed successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('Webhook processing failed');
            });
    }

    private function schedulePriceJobs(Schedule $schedule, LoggerInterface $logger): void
    {
        // Price System: Daily price history capture
        $schedule->job(new FetchDailyPriceUpdates)->dailyAt('01:00')
            ->onSuccess(function () use ($logger): void {
                $logger->info('Daily price updates captured successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('Daily price updates capture failed');
            });

        // Price Alerts: notify users when targets are met
        $schedule->job(new SendPriceAlert)->hourly()
            ->onSuccess(function () use ($logger): void {
                $logger->info('Price alerts dispatched successfully');
            })
            ->onFailure(function () use ($logger): void {
                $logger->error('Price alert dispatch failed');
            });
    }
}
