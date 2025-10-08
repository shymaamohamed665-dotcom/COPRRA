<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Artisan;

final class CacheOptimizer
{
    public function __construct(private readonly OutputStyle $output) {}

    public function clearCaches(): void
    {
        $this->output->info('🧹 Clearing caches...');

        $commands = [
            'cache:clear' => 'Application cache',
            'config:clear' => 'Configuration cache',
            'route:clear' => 'Route cache',
            'view:clear' => 'View cache',
            'event:clear' => 'Event cache',
        ];

        foreach ($commands as $command => $description) {
            try {
                Artisan::call($command);
                $this->output->line("  ✓ Cleared: {$description}");
            } catch (\Exception $e) {
                $this->output->warn("  ✗ Failed to clear: {$description}");
            }
        }

        $this->output->newLine();
    }

    public function optimizeCaches(): void
    {
        $this->output->info('⚡ Optimizing caches...');

        $commands = [
            'config:cache' => 'Configuration cache',
            'route:cache' => 'Route cache',
            'view:cache' => 'View cache',
            'event:cache' => 'Event cache',
        ];

        foreach ($commands as $command => $description) {
            try {
                Artisan::call($command);
                $this->output->line("  ✓ Cached: {$description}");
            } catch (\Exception $e) {
                $this->output->warn("  ✗ Failed to cache: {$description}");
            }
        }

        $this->output->newLine();
    }
}
