<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;

final class SystemOptimizerService
{
    public function __construct(
        private readonly OutputStyle $output
    ) {}

    public function optimizeAutoloader(): void
    {
        $this->executeOptimizationTask(
            '📦 Optimizing autoloader...',
            function (): void {
                $returnCode = 0;
                exec('composer dump-autoload --optimize --no-dev 2>&1', $output, $returnCode);

                if ($returnCode !== 0) {
                    throw new \Exception('Composer autoload optimization failed');
                }
            },
            'Autoloader optimized'
        );
    }

    public function optimizeViews(): void
    {
        $this->executeOptimizationTask(
            '👁️  Optimizing views...',
            function (): void {
                \Illuminate\Support\Facades\Artisan::call('view:cache');
            },
            'Views compiled and cached'
        );
    }

    public function optimizeRoutes(): void
    {
        $this->executeOptimizationTask(
            '🛣️  Optimizing routes...',
            function (): void {
                \Illuminate\Support\Facades\Artisan::call('route:cache');
            },
            'Routes cached'
        );
    }

    public function optimizeConfig(): void
    {
        $this->executeOptimizationTask(
            '⚙️  Optimizing configuration...',
            function (): void {
                \Illuminate\Support\Facades\Artisan::call('config:cache');
            },
            'Configuration cached'
        );
    }

    /**
     * Execute a single optimization task.
     */
    private function executeOptimizationTask(string $title, callable $task, string $successMessage): void
    {
        $this->output->info($title);

        try {
            $task();
            $this->output->line('  ✓ '.$successMessage);
        } catch (\Exception $e) {
            $this->output->warn('  ✗ Failed: '.$e->getMessage());
        }

        $this->output->newLine();
    }
}
