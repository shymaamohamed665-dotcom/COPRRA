<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Console\OutputStyle;

final readonly class CacheStatisticsDisplayer
{
    public function __construct(private OutputStyle $output)
    {
    }

    /**
     * @param  array<string, string|int|float|array|null>  $stats
     */
    public function display(array $stats): void
    {
        $this->output->info('📊 Cache Statistics');
        $this->output->newLine();

        $this->displayCacheTable($stats);
        $this->displayCachePrefixes($stats);
        $this->displayCacheDurations($stats);
        $this->displayRedisStatistics($stats);
    }

    /**
     * @param  array<string, string|int|float|array|null>  $stats
     */
    private function displayCacheTable(array $stats): void
    {
        $this->output->table(
            ['Setting', 'Value'],
            [
                ['Cache Driver', $stats['driver'] ?? 'unknown'],
            ]
        );
    }

    /**
     * @param  array<string, string|int|float|array|null>  $stats
     */
    private function displayCachePrefixes(array $stats): void
    {
        $this->output->newLine();
        $this->output->info('Cache Prefixes:');
        if (is_array($stats['prefixes'] ?? null)) {
            foreach ($stats['prefixes'] as $name => $prefix) {
                $this->output->line("  {$name}: {$prefix}");
            }
        }
    }

    /**
     * @param  array<string, string|int|float|array|null>  $stats
     */
    private function displayCacheDurations(array $stats): void
    {
        $this->output->newLine();
        $this->output->info('Cache Durations:');
        if (is_array($stats['durations'] ?? null)) {
            foreach ($stats['durations'] as $name => $duration) {
                $minutes = is_numeric($duration) ? (float) $duration / 60 : 0;
                $this->output->line("  {$name}: {$duration}s ({$minutes} minutes)");
            }
        }
    }

    /**
     * @param  array<string, string|int|float|array<string, string|int|float>>  $stats
     */
    private function displayRedisStatistics(array $stats): void
    {
        if (isset($stats['redis']) && is_array($stats['redis'])) {
            $this->output->newLine();
            $this->output->info('Redis Statistics:');
            foreach ($stats['redis'] as $key => $value) {
                $this->output->line("  {$key}: {$value}");
            }
        }
    }
}
