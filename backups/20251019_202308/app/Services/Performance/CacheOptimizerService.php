<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;
use Illuminate\Contracts\Console\Kernel;

final readonly class CacheOptimizerService
{
    public function __construct(
        private OutputStyle $output,
        private Kernel $kernel
    ) {}

    public function clearCaches(): void
    {
        $this->output->info('🧹 Clearing caches...');

        $this->executeCommands([
            'cache:clear' => 'Cleared application cache',
            'config:clear' => 'Cleared configuration cache',
            'route:clear' => 'Cleared route cache',
            'view:clear' => 'Cleared view cache',
            'event:clear' => 'Cleared event cache',
        ]);

        $this->output->newLine();
    }

    public function optimizeCaches(): void
    {
        $this->output->info('⚡ Optimizing caches...');

        $this->executeCommands([
            'config:cache' => 'Cached configuration',
            'route:cache' => 'Cached routes',
            'view:cache' => 'Cached views',
            'event:cache' => 'Cached events',
        ]);

        $this->output->newLine();
    }

    /**
     * Execute multiple Artisan commands.
     *
     * @param  array<string, string>  $commands
     */
    private function executeCommands(array $commands): void
    {
        foreach ($commands as $command => $successMessage) {
            $this->executeSingleCommand($command, $successMessage);
        }
    }

    /**
     * Execute a single Artisan command.
     */
    private function executeSingleCommand(string $command, string $successMessage): void
    {
        try {
            $this->kernel->call($command);
            $this->output->line("  ✓ {$successMessage}");
        } catch (\Throwable $exception) {
            $this->output->error("  ✗ Failed to execute {$command}: {$exception->getMessage()}");
        }
    }
}
