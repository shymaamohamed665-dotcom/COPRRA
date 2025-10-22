<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\DB;

// use Illuminate\Support\Facades\Log; // replaced with logger() helper

final class PerformanceMonitoringService
{
    /**
     * @var array<string, array<string, float|int|array>>
     */
    private array $metrics = [];

    private readonly float $startTime;

    private readonly int $startMemory;

    public function __construct()
    {
        $this->startTime = microtime(true);
        $this->startMemory = memory_get_usage();
    }

    /**
     * Start monitoring a specific operation.
     */
    public function startOperation(string $operation): void
    {
        $this->metrics[$operation] = [
            'start_time' => microtime(true),
            'start_memory' => memory_get_usage(),
            'queries' => DB::getQueryLog(),
        ];
    }

    /**
     * End monitoring a specific operation.
     *
     * @return array<string, float|int|string|array>
     */
    public function endOperation(string $operationName): array
    {
        if (! isset($this->metrics[$operationName])) {
            return [];
        }
        $operationData = $this->metrics[$operationName];
        if (
            ! is_array($operationData) ||
            ! isset($operationData['start_time'], $operationData['start_memory'], $operationData['queries'])
        ) {
            return [];
        }
        $endTime = microtime(true);
        $endMemory = memory_get_usage();

        $operationData = $this->metrics[$operationName];

        // Ensure operationData has the expected structure
        if (! is_array($operationData) || ! isset($operationData['start_time'], $operationData['start_memory'], $operationData['queries'])) {
            return [];
        }

        $startTime = is_numeric($operationData['start_time']) ? (float) $operationData['start_time'] : 0.0;
        $startMemory = is_numeric($operationData['start_memory']) ? (int) $operationData['start_memory'] : 0;
        $queries = is_array($operationData['queries']) ? $operationData['queries'] : [];

        $result = [
            'operation' => $operationName,
            'execution_time' => $endTime - $startTime,
            'memory_usage' => $endMemory - $startMemory,
            'peak_memory' => memory_get_peak_usage(),
            'queries_count' => count(DB::getQueryLog()) - count($queries),
            'queries' => array_slice(DB::getQueryLog(), count($queries)),
        ];

        // Check thresholds
        $this->checkThresholds($result);

        unset($this->metrics[$operationName]);

        return $result;
    }

    /**
     * Get overall performance metrics.
     *
     * @return array<array<array>|float|int>
     *
     * @psalm-return array{total_execution_time: float, total_memory_usage: int, peak_memory: int, total_queries: int<0, max>, queries: array<array>, cache_hits: int, cache_misses: int}
     */
    public function getOverallMetrics(): array
    {
        $currentTime = microtime(true);
        $currentMemory = memory_get_usage();

        return [
            'total_execution_time' => $currentTime - $this->startTime,
            'total_memory_usage' => $currentMemory - $this->startMemory,
            'peak_memory' => memory_get_peak_usage(),
            'total_queries' => count(DB::getQueryLog()),
            'queries' => DB::getQueryLog(),
            'cache_hits' => $this->getCacheHits(),
            'cache_misses' => $this->getCacheMisses(),
        ];
    }

    /**
     * Monitor database performance.
     *
     * @return array<string, int|float|list<array<string, string|int|list<string>>>>
     */
    public function monitorDatabase(): array
    {
        $queries = DB::getQueryLog();
        $slowQueries = [];
        $totalTime = 0;

        foreach ($queries as $query) {
            $executionTime = $query['time'];
            $totalTime += $executionTime;

            if ($executionTime > config('monitoring.performance.slow_query_threshold', 1000)) {
                $slowQueries[] = [
                    'sql' => $query['query'],
                    'bindings' => $query['bindings'],
                    'time' => $executionTime,
                ];
            }
        }

        return [
            'total_queries' => count($queries),
            'total_time' => $totalTime,
            'average_time' => count($queries) > 0 ? $totalTime / count($queries) : 0,
            'slow_queries' => $slowQueries,
            'slow_queries_count' => count($slowQueries),
        ];
    }

    /**
     * Monitor cache performance.
     *
     * @return array<float|int>
     *
     * @psalm-return array{hits: int, misses: int, hit_rate: float|int, miss_rate: float|int}
     */
    public function monitorCache(): array
    {
        $hits = $this->getCacheHits();
        $misses = $this->getCacheMisses();
        $total = $hits + $misses;

        return [
            'hits' => $hits,
            'misses' => $misses,
            'hit_rate' => $total > 0 ? $hits / $total : 0,
            'miss_rate' => $total > 0 ? $misses / $total : 0,
        ];
    }

    /**
     * Monitor memory usage.
     *
     * @return array<float|int>
     *
     * @psalm-return array{current_usage: int, peak_usage: int, limit: int, usage_percentage: float|int}
     */
    public function monitorMemory(): array
    {
        $currentMemory = memory_get_usage();
        $peakMemory = memory_get_peak_usage();
        $limit = ini_get('memory_limit');

        return [
            'current_usage' => $currentMemory,
            'peak_usage' => $peakMemory,
            'limit' => $this->parseMemoryLimit($limit),
            'usage_percentage' => $this->parseMemoryLimit($limit) > 0
                ? $currentMemory / $this->parseMemoryLimit($limit) * 100
                : 0,
        ];
    }

    /**
     * Monitor storage usage.
     *
     * @return array<false|float|int>
     *
     * @psalm-return array{total_space: false|float, used_space: float, free_space: false|float, usage_percentage: 0|float}
     */
    public function monitorStorage(): array
    {
        $storagePath = storage_path();
        $totalSpace = disk_total_space($storagePath);
        $freeSpace = disk_free_space($storagePath);
        $usedSpace = $totalSpace - $freeSpace;

        return [
            'total_space' => $totalSpace,
            'used_space' => $usedSpace,
            'free_space' => $freeSpace,
            'usage_percentage' => $totalSpace > 0 ? $usedSpace / $totalSpace * 100 : 0,
        ];
    }

    /**
     * Log performance metrics.
     */
    public function logMetrics(): void
    {
        $metrics = $this->getOverallMetrics();

        logger()->info('Performance Metrics', [
            'execution_time' => $metrics['total_execution_time'],
            'memory_usage' => $metrics['total_memory_usage'],
            'peak_memory' => $metrics['peak_memory'],
            'queries_count' => $metrics['total_queries'],
            'cache_hits' => $metrics['cache_hits'],
            'cache_misses' => $metrics['cache_misses'],
        ]);
    }

    /**
     * Check performance thresholds.
     *
     * @param  array<string, float|int|string|array>  $metrics
     */
    private function checkThresholds(array $metrics): void
    {
        $config = config('monitoring.performance', []);

        // Check execution time
        if (
            $this->isExecutionTimeExceeded($metrics, $config)
        ) {
            logger()->warning('Slow operation detected', [
                'operation' => $metrics['operation'] ?? 'unknown',
                'execution_time' => $metrics['execution_time'],
                'threshold' => $config['execution_time_threshold'],
            ]);
        }

        // Check memory usage
        if ($this->isMemoryUsageExceeded($metrics, $config)) {
            logger()->warning('High memory usage detected', [
                'operation' => $metrics['operation'] ?? 'unknown',
                'memory_usage' => $metrics['memory_usage'],
                'threshold' => $config['memory_threshold'],
            ]);
        }

        // Check query count
        if ($this->isQueryCountExceeded($metrics, $config)) {
            logger()->warning('High query count detected', [
                'operation' => $metrics['operation'] ?? 'unknown',
                'queries_count' => $metrics['queries_count'],
                'threshold' => $config['query_count_threshold'],
            ]);
        }
    }

    /**
     * Get cache hits (simplified implementation).
     */
    private function getCacheHits(): int
    {
        // This would need to be implemented based on your cache driver
        return 0;
    }

    /**
     * Get cache misses (simplified implementation).
     */
    private function getCacheMisses(): int
    {
        // This would need to be implemented based on your cache driver
        return 0;
    }

    /**
     * Parse memory limit string to bytes.
     */
    private function parseMemoryLimit(string $limit): int
    {
        $limit = trim($limit);
        $last = strtolower($limit[strlen($limit) - 1]);
        $limit = (int) $limit;

        switch ($last) {
            case 'g':
                $limit *= 1024;
                // fall through
                // no break
            case 'm':
                $limit *= 1024;
                // fall through
                // no break
            case 'k':
                $limit *= 1024;
        }

        return $limit;
    }

    /**
     * @param  array<string, float|int|string|array>  $metrics
     * @param  array<string, scalar|array|null>  $config
     */
    private function isExecutionTimeExceeded(array $metrics, array $config): bool
    {
        return is_array($config) &&
            isset($config['execution_time_threshold']) &&
            is_numeric($config['execution_time_threshold']) &&
            is_numeric($metrics['execution_time']) &&
            $metrics['execution_time'] > (float) $config['execution_time_threshold'];
    }

    /**
     * @param  array<string, float|int|string|array>  $metrics
     * @param  array<string, scalar|array|null>  $config
     */
    private function isMemoryUsageExceeded(array $metrics, array $config): bool
    {
        return is_array($config) &&
            isset($config['memory_threshold']) &&
            is_numeric($config['memory_threshold']) &&
            is_numeric($metrics['memory_usage']) &&
            $metrics['memory_usage'] > ((float) $config['memory_threshold'] * 1024 * 1024);
    }

    /**
     * @param  array<string, float|int|string|array>  $metrics
     * @param  array<string, scalar|array|null>  $config
     */
    private function isQueryCountExceeded(array $metrics, array $config): bool
    {
        return is_array($config) &&
            isset($config['query_count_threshold']) &&
            is_numeric($config['query_count_threshold']) &&
            is_numeric($metrics['queries_count']) &&
            $metrics['queries_count'] > (int) $config['query_count_threshold'];
    }
}
