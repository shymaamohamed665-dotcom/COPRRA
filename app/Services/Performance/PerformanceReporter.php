<?php

declare(strict_types=1);

namespace App\Services\Performance;

use Illuminate\Console\OutputStyle;
use Illuminate\Support\Facades\Http;

final class PerformanceReporter
{
    public function __construct(private readonly OutputStyle $output) {}

    public function displayRecommendations(): void
    {
        $this->output->info('💡 Recommendations...');

        $recommendations = [
            'Enable OPcache' => extension_loaded('Zend OPcache') && ini_get('opcache.enable'),
            'Enable Redis for caching' => config('cache.default') === 'redis',
            'Use a CDN for assets' => false, // Placeholder
            'Enable gzip compression' => false, // Placeholder
        ];

        foreach ($recommendations as $recommendation => $enabled) {
            $status = $enabled ? '✓' : '✗';
            $this->output->line("  {$status} {$recommendation}");
        }

        $this->output->newLine();
    }

    public function displayStatistics(): void
    {
        $this->output->info('📊 Statistics...');

        $this->displayOpcacheStatus();
        $this->displayRealTimeRequests();

        $this->output->newLine();
    }

    private function displayOpcacheStatus(): void
    {
        if (extension_loaded('Zend OPcache') && function_exists('opcache_get_status')) {
            $opcacheStatus = opcache_get_status();

            if ($opcacheStatus && $opcacheStatus['opcache_enabled']) {
                $this->output->line('  OPcache status: ✓ Enabled');
                $this->output->table(
                    ['Metric', 'Value'],
                    [
                        ['Used memory', number_format($opcacheStatus['memory_usage']['used_memory'] / 1024 / 1024, 2).' MB'],
                        ['Free memory', number_format($opcacheStatus['memory_usage']['free_memory'] / 1024 / 1024, 2).' MB'],
                        ['Wasted memory', number_format($opcacheStatus['memory_usage']['wasted_memory'] / 1024 / 1024, 2).' MB'],
                        ['Hit rate', number_format($opcacheStatus['opcache_statistics']['opcache_hit_rate'], 2).' %'],
                        ['Cached scripts', $opcacheStatus['opcache_statistics']['num_cached_scripts']],
                    ]
                );
            } else {
                $this->output->line('  OPcache status: ✗ Disabled');
            }
        } else {
            $this->output->line('  OPcache status: ✗ Not available');
        }

        $this->output->newLine();
    }

    private function displayRealTimeRequests(): void
    {
        $this->output->line('  Real-time requests:');

        try {
            $response = Http::get('http://localhost/server-status?auto');

            if ($response->successful()) {
                $this->output->text($response->body());
            } else {
                $this->output->warn('  Could not fetch real-time requests. Is mod_status enabled?');
            }
        } catch (\Exception $e) {
            $this->output->warn('  Could not fetch real-time requests: '.$e->getMessage());
        }
    }
}
