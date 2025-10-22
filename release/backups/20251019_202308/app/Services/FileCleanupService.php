<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\FileCleanup\CleanupStrategyFactory;
use App\Services\FileCleanup\DirectoryCleaner;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class FileCleanupService
{
    /** @var array<string, int|string> */
    public array $config = [];

    private readonly DirectoryCleaner $cleaner;

    public function __construct(?DirectoryCleaner $cleaner = null)
    {
        $this->config = config('file_cleanup', [
            'temp_files_retention_days' => 7,
            'log_files_retention_days' => 30,
            'cache_files_retention_days' => 14,
            'backup_files_retention_days' => 90,
            'max_storage_size_mb' => 1024, // 1GB
            'cleanup_schedule' => 'daily',
        ]);
        $this->cleaner = $cleaner ?? new DirectoryCleaner();
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array<string, int|list<string>|string>
     */
    public function cleanupTempFiles(): array
    {
        try {
            $result = $this->runCleanup('temp');
            Log::info('Temp files cleanup completed', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('Temp files cleanup failed', ['error' => $e->getMessage()]);

            return [
                'temp_files' => 0,
                'deleted_size' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array<string, int|list<string>|string>
     */
    public function cleanupLogFiles(): array
    {
        try {
            $result = $this->runCleanup('logs');
            Log::info('Log files cleanup completed', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('Log files cleanup failed', ['error' => $e->getMessage()]);

            return [
                'log_files' => 0,
                'deleted_size' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array<string, int|list<string>|string>
     */
    public function cleanupCacheFiles(): array
    {
        try {
            $result = $this->runCleanup('cache');
            Log::info('Cache files cleanup completed', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('Cache files cleanup failed', ['error' => $e->getMessage()]);

            return [
                'cache_files' => 0,
                'deleted_size' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array<string, int|list<string>|string>
     */
    public function cleanupBackupFiles(): array
    {
        try {
            $result = $this->runCleanup('backups');
            Log::info('Backup files cleanup completed', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('Backup files cleanup failed', ['error' => $e->getMessage()]);

            return [
                'backup_files' => 0,
                'deleted_size' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array<string, int|list<string>|string>
     */
    public function cleanupUploadedFiles(): array
    {
        try {
            $result = $this->runCleanup('uploads');
            Log::info('Uploaded files cleanup completed', $result);

            return $result;
        } catch (Exception $e) {
            Log::error('Uploaded files cleanup failed', ['error' => $e->getMessage()]);

            return [
                'uploaded_files' => 0,
                'deleted_size' => 0,
                'errors' => [$e->getMessage()],
            ];
        }
    }

    /**
     * @return array<array<int|string|array<string>>|float|int>
     *
     * @psalm-return array{temp_files: array<string, array<int, string>|int|string>, log_files: array<string, array<int, string>|int|string>, cache_files: array<string, array<int, string>|int|string>, backup_files: array<string, array<int, string>|int|string>, uploaded_files: array<string, array<int, string>|int|string>, total_files_deleted: float|int, total_size_deleted: int}
     */
    public function performCompleteCleanup(): array
    {
        $results = [
            'temp_files' => $this->cleanupTempFiles(),
            'log_files' => $this->cleanupLogFiles(),
            'cache_files' => $this->cleanupCacheFiles(),
            'backup_files' => $this->cleanupBackupFiles(),
            'uploaded_files' => $this->cleanupUploadedFiles(),
        ];

        $totals = $this->calculateTotals($results);

        $finalResult = array_merge($results, $totals);

        Log::info('Complete file cleanup performed', $finalResult);

        return $finalResult;
    }

    /**
     * @return array<bool|float|int>
     *
     * @psalm-return array{current_size_mb: float, max_size_mb: float, usage_percentage: 0|float, needs_cleanup: bool}
     */
    public function checkStorageUsage(): array
    {
        $storagePath = storage_path();
        $totalSize = $this->getDirectorySize($storagePath);
        $maxSizeMb = (float) ($this->config['max_storage_size_mb'] ?? 1024.0);
        $maxSize = $maxSizeMb * 1024 * 1024;

        return [
            'current_size_mb' => round($totalSize / 1024 / 1024, 2),
            'max_size_mb' => $maxSizeMb,
            'usage_percentage' => $maxSize > 0 ? round($totalSize / $maxSize * 100, 2) : 0,
            'needs_cleanup' => $totalSize > $maxSize,
        ];
    }

    /**
     * @return array<array<scalar>|string|null>
     *
     * @psalm-return array{storage_usage: array<string, bool|float|int>, config: array<string, int|string>, last_cleanup: string|null, next_cleanup: string|null}
     */
    public function getCleanupStatistics(): array
    {
        return [
            'storage_usage' => $this->checkStorageUsage(),
            'config' => $this->config,
            'last_cleanup' => $this->getLastCleanupTime(),
            'next_cleanup' => $this->getNextCleanupTime(),
        ];
    }

    public function scheduleCleanup(): void
    {
        $schedule = (string) ($this->config['cleanup_schedule'] ?? 'daily');

        if ($schedule === 'daily') {
            Artisan::call('schedule:run');

            return;
        }

        if ($schedule === 'weekly') {
            $now = now();
            if (method_exists($now, 'isSunday') ? $now->isSunday() : Carbon::now()->isSunday()) {
                Artisan::call('schedule:run');
            }
        }
    }

    /**
     * @return array<int|string|array<string>>
     *
     * @psalm-return array<string, int|list<string>|string>
     */
    private function runCleanup(string $type): array
    {
        $strategy = CleanupStrategyFactory::create($type, $this->cleaner, $this->config);
        if ($strategy) {
            return $strategy->cleanup();
        }

        return ['error' => "Invalid cleanup type: {$type}"];
    }

    /**
     * @param  array<string, array<string, int|string>>  $results
     *
     * @return array<float|int>
     *
     * @psalm-return array{total_files_deleted: float|int<min, max>, total_size_deleted: int<min, max>}
     */
    private function calculateTotals(array $results): array
    {
        $totalFilesDeleted = 0;
        $totalSizeDeleted = 0;

        foreach ($results as $result) {
            if (is_array($result)) {
                $totalFilesDeleted += array_sum(array_intersect_key($result, array_flip(preg_grep('/_files$/', array_keys($result)))));
                $totalSizeDeleted += $result['deleted_size'] ?? 0;
            }
        }

        return [
            'total_files_deleted' => $totalFilesDeleted,
            'total_size_deleted' => $totalSizeDeleted,
        ];
    }

    /**
     * @psalm-return int<min, max>
     */
    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        if (! is_dir($directory)) {
            return $size;
        }

        $items = scandir($directory);
        if ($items === false) {
            return $size;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $directory.DIRECTORY_SEPARATOR.$item;

            if (is_file($path)) {
                $fileSize = filesize($path);
                if ($fileSize !== false) {
                    $size += $fileSize;
                }
            } elseif (is_dir($path)) {
                $size += $this->getDirectorySize($path);
            }
        }

        return $size;
    }

    private function getLastCleanupTime(): string|false|null
    {
        $lastCleanupFile = storage_path('logs/last_cleanup.log');

        if (file_exists($lastCleanupFile)) {
            return file_get_contents($lastCleanupFile) ? file_get_contents($lastCleanupFile) : null;
        }

        return null;
    }

    private function getNextCleanupTime(): ?string
    {
        $lastCleanup = $this->getLastCleanupTime();

        if ($lastCleanup !== null) {
            try {
                return Carbon::parse($lastCleanup)->addDay()->toISOString();
            } catch (\Exception $e) {
                Log::error('Failed to parse last cleanup time', [
                    'error' => $e->getMessage(),
                    'last_cleanup_time' => $lastCleanup,
                ]);
                // Fallback
            }
        }

        return Carbon::now()->addDay()->toISOString();
    }
}
