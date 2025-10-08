<?php

declare(strict_types=1);

namespace App\Services\LogProcessing;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class SystemHealthChecker
{
    /**
     * Check all system health components
     *
     * @return array{
     *     database: array{status: string, message: string},
     *     cache: array{status: string, message: string},
     *     storage: array{status: string, message: string},
     *     memory: array{status: string, message: string},
     *     disk_space: array{status: string, message: string}
     * }
     */
    public function checkAll(): array
    {
        return [
            'database' => $this->checkDatabaseHealth(),
            'cache' => $this->checkCacheHealth(),
            'storage' => $this->checkStorageHealth(),
            'memory' => $this->checkMemoryHealth(),
            'disk_space' => $this->checkDiskSpaceHealth(),
        ];
    }

    /**
     * Check database health
     *
     * @return array<string, string>
     */
    private function checkDatabaseHealth(): array
    {
        try {
            DB::select('SELECT 1');

            return ['status' => 'healthy', 'message' => 'Database connection successful'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Database connection failed: '.$e->getMessage()];
        }
    }

    /**
     * Check cache health
     *
     * @return array<string, string>
     */
    private function checkCacheHealth(): array
    {
        try {
            $testKey = 'health_check_'.time();
            Cache::put($testKey, 'test', 60);
            $retrieved = Cache::get($testKey);
            Cache::forget($testKey);

            if ($retrieved === 'test') {
                return ['status' => 'healthy', 'message' => 'Cache is working'];
            }

            return ['status' => 'warning', 'message' => 'Cache test failed'];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Cache error: '.$e->getMessage()];
        }
    }

    /**
     * Check storage health
     *
     * @return array<string, string>
     */
    private function checkStorageHealth(): array
    {
        try {
            $storagePath = storage_path();
            $totalSpace = disk_total_space($storagePath);
            $freeSpace = disk_free_space($storagePath);
            $usedSpace = $totalSpace - $freeSpace;
            $usagePercentage = $usedSpace / $totalSpace * 100;

            if ($usagePercentage > 90) {
                return [
                    'status' => 'critical',
                    'message' => 'Storage usage critical: '.round($usagePercentage, 2).'%',
                ];
            }

            if ($usagePercentage > 80) {
                return [
                    'status' => 'warning',
                    'message' => 'Storage usage high: '.round($usagePercentage, 2).'%',
                ];
            }

            return [
                'status' => 'healthy',
                'message' => 'Storage usage normal: '.round($usagePercentage, 2).'%',
            ];
        } catch (\Exception $e) {
            return ['status' => 'critical', 'message' => 'Storage check failed: '.$e->getMessage()];
        }
    }

    /**
     * Check memory health
     *
     * @return array<string, string>
     */
    private function checkMemoryHealth(): array
    {
        $memoryUsage = memory_get_usage(true);
        $memoryLimit = ini_get('memory_limit');
        $memoryLimitBytes = $this->convertToBytes($memoryLimit);
        $usagePercentage = $memoryUsage / $memoryLimitBytes * 100;

        if ($usagePercentage > 90) {
            return [
                'status' => 'critical',
                'message' => 'Memory usage critical: '.round($usagePercentage, 2).'%',
            ];
        }

        if ($usagePercentage > 80) {
            return [
                'status' => 'warning',
                'message' => 'Memory usage high: '.round($usagePercentage, 2).'%',
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Memory usage normal: '.round($usagePercentage, 2).'%',
        ];
    }

    /**
     * Check disk space health
     *
     * @return array<string, string>
     */
    private function checkDiskSpaceHealth(): array
    {
        $diskSpace = disk_free_space(storage_path());
        $diskSpaceGB = $diskSpace / (1024 * 1024 * 1024);

        if ($diskSpaceGB < 1) {
            return [
                'status' => 'critical',
                'message' => 'Disk space critical: '.round($diskSpaceGB, 2).'GB free',
            ];
        }

        if ($diskSpaceGB < 5) {
            return [
                'status' => 'warning',
                'message' => 'Disk space low: '.round($diskSpaceGB, 2).'GB free',
            ];
        }

        return [
            'status' => 'healthy',
            'message' => 'Disk space normal: '.round($diskSpaceGB, 2).'GB free',
        ];
    }

    /**
     * Convert memory limit to bytes
     */
    private function convertToBytes(string $memoryLimit): int
    {
        $memoryLimit = trim($memoryLimit);
        $unit = strtolower($memoryLimit[strlen($memoryLimit) - 1]);
        $value = (int) $memoryLimit;

        return match ($unit) {
            'g' => $value * 1024 * 1024 * 1024,
            'm' => $value * 1024 * 1024,
            'k' => $value * 1024,
            default => $value,
        };
    }
}
