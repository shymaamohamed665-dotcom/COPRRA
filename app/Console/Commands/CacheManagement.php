<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\CacheService;
use App\Services\CacheStatisticsDisplayer;
use Illuminate\Console\Command;

/**
 * @property string $signature
 * @property string $description
 */

/**
 * @property string $signature
 * @property string $description
 */
final class CacheManagement extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'cache:manage
                            {action : Action to perform (stats, clear-prices, clear-search, clear-all)}
                            {--force : Force the action without confirmation}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Manage COPRRA cache system';

    /**
     * Execute the console command.
     */
    public function handle(CacheService $cacheService): int
    {
        $action = $this->argument('action');

        if (! is_string($action)) {
            $this->error('Invalid action argument.');

            return self::FAILURE;
        }

        $validatedAction = $this->validateAction($action);
        if ($validatedAction === null) {
            return self::FAILURE;
        }

        return $this->executeAction($validatedAction, $cacheService);
    }

    /**
     * Show cache statistics.
     */
    protected function showStatistics(CacheService $cacheService): int
    {
        $stats = $cacheService->getStatistics();
        $displayer = new CacheStatisticsDisplayer($this->output);
        $displayer->display($stats);

        return self::SUCCESS;
    }

    /**
     * Clear price comparison caches.
     */
    protected function clearPriceComparisons(CacheService $cacheService): int
    {
        if (! $this->confirmAction('Are you sure you want to clear all price comparison caches?')) {
            return self::SUCCESS;
        }

        $this->info('🗑️  Clearing price comparison caches...');

        $count = $cacheService->invalidateAllPriceComparisons();

        $this->info('✅ Cleared '.$count.' price comparison cache entries.');

        return self::SUCCESS;
    }

    /**
     * Clear search result caches.
     */
    protected function clearSearchResults(CacheService $cacheService): int
    {
        if (! $this->confirmAction('Are you sure you want to clear all search result caches?')) {
            return self::SUCCESS;
        }

        $this->info('🗑️  Clearing search result caches...');

        $count = $cacheService->invalidateAllSearches();

        $this->info('✅ Cleared '.$count.' search result cache entries.');

        return self::SUCCESS;
    }

    /**
     * Clear all cache.
     */
    protected function clearAllCache(CacheService $cacheService): int
    {
        if (! $this->confirmAction('⚠️  This will clear ALL cache. Are you sure?')) {
            return self::SUCCESS;
        }

        $this->warn('🗑️  Clearing ALL cache...');

        $cacheService->clearAll();

        $this->info('✅ All cache cleared successfully.');

        return self::SUCCESS;
    }

    private function validateAction(string $action): ?string
    {
        $validActions = ['stats', 'clear-prices', 'clear-search', 'clear-all'];
        if (! in_array($action, $validActions, true)) {
            $this->error("Unknown action: {$action}");

            return null;
        }

        return $action;
    }

    private function executeAction(string $action, CacheService $cacheService): int
    {
        $actions = [
            'stats' => fn () => $this->showStatistics($cacheService),
            'clear-prices' => fn () => $this->clearPriceComparisons($cacheService),
            'clear-search' => fn () => $this->clearSearchResults($cacheService),
            'clear-all' => fn () => $this->clearAllCache($cacheService),
        ];

        $result = $actions[$action]();

        return is_int($result) ? $result : self::SUCCESS;
    }

    /**
     * Confirm the action with the user.
     */
    private function confirmAction(string $message): bool
    {
        if ($this->option('force')) {
            return true;
        }

        if (! $this->confirm($message)) {
            $this->info('Operation cancelled.');

            return false;
        }

        return true;
    }
}
