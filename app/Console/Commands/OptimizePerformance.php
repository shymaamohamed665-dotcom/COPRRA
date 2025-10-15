<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Performance\CacheOptimizerService;
use App\Services\Performance\DatabaseOptimizerService;
use App\Services\Performance\PerformanceReporterService;
use App\Services\Performance\SystemOptimizerService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Cache\Repository;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\DatabaseManager;

final class OptimizePerformance extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:optimize-performance
                            {--clear : Clear all caches before optimizing}
                            {--analyze : Analyze database performance}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Optimize application performance (caching, database, etc.)';

    private CacheOptimizerService $cacheOptimizer;

    private DatabaseOptimizerService $databaseOptimizer;

    private SystemOptimizerService $systemOptimizer;

    private PerformanceReporterService $performanceReporter;

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->info('🚀 Starting Performance Optimization...');
        $this->newLine();

        $this->initializeServices();
        $this->handleClearOption();
        $this->runOptimizations();
        $this->handleAnalyzeOption();

        $this->newLine();
        $this->info('✅ Performance optimization completed successfully!');
        $this->newLine();

        $this->performanceReporter->displayRecommendations();
        $this->performanceReporter->displayPerformanceStats();

        return Command::SUCCESS;
    }

    private function initializeServices(): void
    {
        /** @var Kernel $kernel */
        $kernel = $this->laravel->make(Kernel::class);
        /** @var Repository $cache */
        $cache = $this->laravel->make(Repository::class);
        /** @var DatabaseManager $databaseManager */
        $databaseManager = $this->laravel->make(DatabaseManager::class);

        $this->cacheOptimizer = new CacheOptimizerService($this->output, $kernel);
        $this->databaseOptimizer = new DatabaseOptimizerService($this->output, $databaseManager, $kernel);
        $this->systemOptimizer = new SystemOptimizerService($this->output, $kernel);
        $this->performanceReporter = new PerformanceReporterService($this->output, $cache);
    }

    private function handleClearOption(): void
    {
        if ($this->option('clear')) {
            $this->cacheOptimizer->clearCaches();
        }
    }

    private function handleAnalyzeOption(): void
    {
        if ($this->option('analyze')) {
            $this->databaseOptimizer->analyzeDatabase();
        }
    }

    /**
     * Run all optimizations.
     */
    private function runOptimizations(): void
    {
        $this->cacheOptimizer->optimizeCaches();
        $this->databaseOptimizer->optimizeDatabase();
        $this->systemOptimizer->optimizeAutoloader();
        $this->systemOptimizer->optimizeViews();
        $this->systemOptimizer->optimizeRoutes();
        $this->systemOptimizer->optimizeConfig();
    }

    /**
     * Display performance recommendations.
     */
    // Removed unused private methods to satisfy PHPMD UnusedPrivateMethod
}
