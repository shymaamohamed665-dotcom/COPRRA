<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;

final class AppOptimizer
{
    public function __construct(private readonly OutputStyle $output) {}

    public function optimizeApp(): void
    {
        $this->output->info('🚀 Optimizing application...');

        $this->optimizeAutoloader();
        $this->optimizeConfig();
        $this->optimizeRoutes();
        $this->optimizeViews();

        $this->output->newLine();
    }

    private function optimizeAutoloader(): void
    {
        $this->output->line('  Optimizing autoloader...');
        shell_exec('composer dump-autoload -o');
        $this->output->line('  ✓ Autoloader optimized');
    }

    private function optimizeConfig(): void
    {
        try {
            Artisan::call('config:cache');
            $this->output->line('  ✓ Configuration cached');
        } catch (\Exception $e) {
            $this->output->warn('  ✗ Failed to cache configuration');
        }
    }

    private function optimizeRoutes(): void
    {
        try {
            Artisan::call('route:cache');
            $this->output->line('  ✓ Routes cached');
        } catch (\Exception $e) {
            $this->output->warn('  ✗ Failed to cache routes');
        }
    }

    private function optimizeViews(): void
    {
        try {
            Artisan::call('view:cache');
            $this->output->line('  ✓ Views cached');
        } catch (\Exception $e) {
            $this->output->warn('  ✗ Failed to cache views');
        }
    }
}
