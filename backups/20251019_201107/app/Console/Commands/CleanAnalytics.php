<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AnalyticsService;
use Illuminate\Console\Command;

/**
 * @property string $signature
 * @property string $description
 */

/**
 * @property string $signature
 * @property string $description
 */
final class CleanAnalytics extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'analytics:clean
                        {--days=30 : Number of days to keep}
                        {--force : Force cleanup without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Clean old analytics data';

    /**
     * Execute the console command.
     */
    public function handle(AnalyticsService $analyticsService): int
    {
        $days = (int) $this->option('days');

        if (! $this->option('force') && ! $this->confirm("This will delete analytics data older than {$days} days. Continue?")) {
            $this->info('Operation cancelled.');

            return self::SUCCESS;
        }

        $this->info("🗑️  Cleaning analytics data older than {$days} days...");

        $count = $analyticsService->cleanOldData($days);

        $this->info("✅ Deleted {$count} old analytics records.");

        return self::SUCCESS;
    }
}
