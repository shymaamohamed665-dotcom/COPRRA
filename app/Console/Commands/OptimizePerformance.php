<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Performance\CacheOptimizerService;
use App\Services\Performance\DatabaseOptimizerService;
use App\Services\Performance\PerformanceReporterService;
use App\Services\Performance\SystemOptimizerService;
use Illuminate\Console\Command;

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

    private CacheOptimizerService $cacheOptimizerService;

    private DatabaseOptimizerService $databaseOptimizerService;

    private SystemOptimizerService $systemOptimizerService;

    private PerformanceReporterService $performanceReporterService;

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

        $this->performanceReporterService->displayRecommendations();
        $this->performanceReporterService->displayPerformanceStats();

        return Command::SUCCESS;
    }

    private function initializeServices(): void
    {
        $this->cacheOptimizerService = new CacheOptimizerService($this->output);
        $this->databaseOptimizerService = new DatabaseOptimizerService($this->output);
        $this->systemOptimizerService = new SystemOptimizerService($this->output);
        $this->performanceReporterService = new PerformanceReporterService($this->output);
    }

    private function handleClearOption(): void
    {
        if ($this->option('clear')) {
            $this->cacheOptimizerService->clearCaches();
        }
    }

    private function handleAnalyzeOption(): void
    {
        if ($this->option('analyze')) {
            $this->databaseOptimizerService->analyzeDatabase();
        }
    }

    /**
     * Run all optimizations.
     */
    private function runOptimizations(): void
    {
        $this->cacheOptimizerService->optimizeCaches();
        $this->databaseOptimizerService->optimizeDatabase();
        $this->systemOptimizerService->optimizeAutoloader();
        $this->systemOptimizerService->optimizeViews();
        $this->systemOptimizerService->optimizeRoutes();
        $this->systemOptimizerService->optimizeConfig();
    }

    /**
     * Display performance recommendations.
     */
    private function displayRecommendations(): void
    {
        $this->performanceReporterService->displayRecommendations();
    }

    /**
     * Display performance statistics.
     */
    private function displayPerformanceStats(): void
    {
        $this->performanceReporterService->displayPerformanceStats();
    }
}
