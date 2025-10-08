<?php

declare(strict_types=1);

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * Define the application's command schedule.
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    #[\Override]
    protected function schedule(Schedule $schedule): void
    {
        // Queue and Cache Management
        $schedule->command('queue:prune-batches')->daily();
        $schedule->command('queue:prune-failed')->weekly();
        $schedule->command('cache:prune-stale-tags')->daily();

        // Log Management (Hostinger has file size limits)
        $schedule->command('log:prune')->daily()
            ->onSuccess(static function (): void {
                \Illuminate\Support\Facades\Log::info('Log pruning completed successfully');
            });

        // Session Cleanup
        $schedule->command('session:gc')->weekly();

        // Storage Cleanup (remove old temporary files)
        $schedule->command('storage:prune-temporary')->daily();

        // Database Maintenance
        $schedule->command('db:monitor')->hourly()
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('Database monitoring failed');
            });

        // Deployment Health Checks
        $schedule->command('deployment:check')->daily()
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('Deployment health check failed');
            });

        // COPRRA: Exchange Rates Update (Daily at 2 AM)
        $schedule->command('exchange-rates:update')->dailyAt('02:00')
            ->onSuccess(static function (): void {
                \Illuminate\Support\Facades\Log::info('Exchange rates updated successfully');
            })
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('Exchange rates update failed');
            });

        // COPRRA: Sitemap Generation (Daily at 3 AM)
        $schedule->command('sitemap:generate')->dailyAt('03:00')
            ->onSuccess(static function (): void {
                \Illuminate\Support\Facades\Log::info('Sitemap generated successfully');
            })
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('Sitemap generation failed');
            });

        // COPRRA: SEO Audit (Weekly on Sunday at 4 AM)
        $schedule->command('seo:audit --fix')->weekly()->sundays()->at('04:00')
            ->onSuccess(static function (): void {
                \Illuminate\Support\Facades\Log::info('SEO audit completed successfully');
            })
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('SEO audit failed');
            });

        // COPRRA: Analytics Cleanup (Monthly on 1st at 5 AM)
        $schedule->command('analytics:clean --days=90 --force')->monthly()->at('05:00')
            ->onSuccess(static function (): void {
                \Illuminate\Support\Facades\Log::info('Analytics cleanup completed successfully');
            })
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('Analytics cleanup failed');
            });

        // COPRRA: Process Pending Webhooks (Every 5 minutes)
        $schedule->command('webhooks:process --limit=100')->everyFiveMinutes()
            ->onSuccess(static function (): void {
                \Illuminate\Support\Facades\Log::info('Webhooks processed successfully');
            })
            ->onFailure(static function (): void {
                \Illuminate\Support\Facades\Log::error('Webhook processing failed');
            });
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
}
