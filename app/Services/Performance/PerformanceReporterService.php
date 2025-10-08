<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;

final class PerformanceReporterService
{
    public function __construct(
        private readonly OutputStyle $output
    ) {}

    public function displayRecommendations(): void
    {
        $this->output->info('💡 Performance Recommendations:');
        $this->output->newLine();

        $recommendations = [
            'Enable OPcache in production (php.ini: opcache.enable=1)',
            'Use Redis for cache and sessions in production',
            'Enable HTTP/2 on your web server',
            'Use a CDN for static assets',
            'Enable Gzip/Brotli compression',
            'Implement lazy loading for images',
            'Use database query caching where appropriate',
            'Monitor slow queries and optimize them',
            'Consider using Laravel Octane for better performance',
            'Implement queue workers for heavy operations',
        ];

        foreach ($recommendations as $index => $recommendation) {
            $this->output->line('  '.($index + 1).'. '.$recommendation);
        }

        $this->output->newLine();
    }

    public function displayPerformanceStats(): void
    {
        $cacheStats = cache()->get('stats') ?? [];
        $dbStats = cache()->get('db_stats') ?? [];
        $opcacheStats = function_exists('opcache_get_status') ? opcache_get_status() : [];
        $memoryUsage = memory_get_usage(true);
        $peakMemoryUsage = memory_get_peak_usage(true);

        $stats = [
            'cache' => $cacheStats,
            'db' => $dbStats,
            'opcache' => $opcacheStats,
            'memory_usage' => (string) $memoryUsage,
            'peak_memory_usage' => (string) $peakMemoryUsage,
        ];

        $this->output->info('Performance stats: '.json_encode($stats));
    }
}
