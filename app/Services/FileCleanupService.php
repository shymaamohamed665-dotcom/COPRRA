<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\FileCleanup\CleanupStrategyFactory;
use App\Services\FileCleanup\DirectoryCleaner;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

final class FileCleanupService
{
    /** @var array<string, int|string> */
    private array $config = [];

    private DirectoryCleaner $cleaner;

    public function __construct(DirectoryCleaner $cleaner)
    {
        $this->config = config('file_cleanup', [
            'temp_files_retention_days' => 7,
            'log_files_retention_days' => 30,
            'cache_files_retention_days' => 14,
            'backup_files_retention_days' => 90,
            'max_storage_size_mb' => 1024, // 1GB
            'cleanup_schedule' => 'daily',
        ]);
        $this->cleaner = $cleaner;
    }

    /**
     * @return array<string, int|string>
     */
    public function cleanupTempFiles(): array
    {
        return $this->runCleanup('temp');
    }

    /**
     * @return array<string, int|string>
     */
    public function cleanupLogFiles(): array
    {
        return $this->runCleanup('logs');
    }

    /**
     * @return array<string, int|string>
     */
    public function cleanupCacheFiles(): array
    {
        return $this->runCleanup('cache');
    }

    /**
     * @return array<string, int|string>
     */
    public function cleanupBackupFiles(): array
    {
        return $this->runCleanup('backups');
    }

    /**
     * @return array<string, int|string>
     */
    public function cleanupUploadedFiles(): array
    {
        return $this->runCleanup('uploads');
    }

    /**
     * @return array<string, bool|string|int|array|null>
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
     * @return array<string, float|int|bool>
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
     * @return array<string, bool|string|int|array|null>
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

    /**
     * @return array<string, int|string|array<int, string>>
     */
    private function runCleanup(string $type): array
    {
        try {
            $strategy = CleanupStrategyFactory::create($type, $this->cleaner, $this->config);
            if ($strategy) {
                return $strategy->cleanup();
            }
        } catch (Exception $e) {
            Log::error("Cleanup failed for type: {$type}", ['error' => $e->getMessage()]);
        }

        return ['error' => "Invalid cleanup type: {$type}"];
    }

    /**
     * @param  array<string, array<string, int|string>>  $results
     * @return array<string, int>
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

    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        if (! is_dir($directory)) {
            return $size;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    private function getLastCleanupTime(): ?string
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
