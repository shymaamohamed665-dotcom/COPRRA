<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\StorageBreakdown;
use App\DataObjects\StorageStatistics;
use App\DataObjects\StorageUsage;
use Exception;
use Illuminate\Contracts\Log\Logger;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class StorageManagementService
{
    /**
     * @var array<string, bool|int|float|array<int, string>>
     */
    private array $config = [];

    public function __construct(
        private readonly FileCleanupService $cleanupService,
        private readonly Logger $logger
    ) {
        $config = config('storage_management', [
            'max_storage_size_mb' => 1024, // 1GB
            'warning_threshold' => 80, // 80%
            'critical_threshold' => 95, // 95%
            'auto_cleanup' => true,
            'cleanup_priority' => ['temp', 'cache', 'logs', 'backups'],
            'compression_enabled' => true,
            'archival_enabled' => true,
        ]);
        $this->config = is_array($config) ? $config : [];
    }

    /**
     * Monitor storage usage.
     */
    public function monitorStorageUsage(): StorageUsage
    {
        $totalSize = $this->getDirectorySize(storage_path());
        $maxSize = $this->getMaxStorageSize();
        $usagePercentage = $maxSize > 0 ? $totalSize / $maxSize * 100 : 0;
        $status = $this->getStorageStatus($usagePercentage);

        $result = $this->buildMonitoringResult($totalSize, $maxSize, $usagePercentage, $status);

        $this->logStorageUsage($status, $result->toArray());

        return $result;
    }

    /**
     * Get storage breakdown by directory.
     *
     * @return array<string, StorageBreakdown>
     */
    public function getStorageBreakdown(): array
    {
        $breakdown = [];
        $directories = $this->getBreakdownDirectories();

        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $size = $this->getDirectorySize($path);
                $breakdown[$name] = new StorageBreakdown(
                    sizeMb: round($size / 1024 / 1024, 2),
                    sizeBytes: $size,
                    path: $path,
                );
            }
        }

        return $breakdown;
    }

    /**
     * Auto cleanup if needed.
     *
     * @return array{
     *     cleanup_performed: bool,
     *     reason?: string,
     *     usage?: array<string, int|float|string>,
     *     cleanup_results?: array<string, string|int|float>,
     *     usage_before?: array<string, int|float|string>,
     *     usage_after?: array<string, int|float|string>
     * }
     */
    public function autoCleanupIfNeeded(): array
    {
        $usage = $this->monitorStorageUsage();

        if (! $this->isCleanupNeeded($usage)) {
            return [
                'cleanup_performed' => false,
                'reason' => 'No cleanup needed or auto cleanup disabled',
                'usage' => $usage->toArray(),
            ];
        }

        return $this->performAndReportCleanup($usage);
    }

    /**
     * Compress old files.
     *
     * @return array{
     *     compression_disabled?: bool,
     *     files_compressed?: int,
     *     space_saved_mb?: float,
     *     errors?: list<string>
     * }
     */
    public function compressOldFiles(): array
    {
        return $this->executeStorageOperation('compression', function (): array {
            return $this->executeCompression();
        }, [
            'files_compressed' => 0,
            'space_saved_mb' => 0,
            'errors' => [],
        ]);
    }

    /**
     * Archive old files.
     *
     * @return array{
     *     archival_disabled?: bool,
     *     files_archived?: int,
     *     archives_created?: int,
     *     space_saved_mb?: float,
     *     errors?: list<string>
     * }
     */
    public function archiveOldFiles(): array
    {
        return $this->executeStorageOperation('archival', function (): array {
            return $this->executeArchival();
        }, [
            'files_archived' => 0,
            'archives_created' => 0,
            'space_saved_mb' => 0,
            'errors' => [],
        ]);
    }

    /**
     * Get storage recommendations.
     *
     * @return list<array<string, string>>
     */
    public function getStorageRecommendations(): array
    {
        $usage = $this->monitorStorageUsage();
        $breakdown = $usage->breakdown;
        $recommendations = [];

        if (is_array($breakdown)) {
            uasort($breakdown, [self::class, 'sortDirectoriesBySize']);
        }

        $this->addUsageRecommendations($usage, $recommendations);
        $this->addDirectoryRecommendations($breakdown, $recommendations);

        return $recommendations;
    }

    /**
     * Update storage limits configuration.
     *
     * @param  array<string, bool|int|float|array<int, string>>  $limits
     */
    public function updateStorageLimits(array $limits): bool
    {
        try {
            $this->config = array_merge($this->config, $limits);

            $this->updateConfigFile();

            $this->logger->info('Storage limits updated', $limits);

            return true;
        } catch (Exception $e) {
            return $this->handleUpdateError($e, $limits);
        }
    }

    /**
     * Get storage statistics.
     */
    public function getStorageStatistics(): StorageStatistics
    {
        $usage = $this->monitorStorageUsage();
        $breakdown = $this->getStorageBreakdown();

        $fileStats = $this->getFileStats($breakdown);

        return $this->buildStorageStatisticsResponse($usage, $breakdown, $fileStats);
    }

    private function logStorageUsage(string $status, array $data): void
    {
        if ($status === 'critical' || $status === 'warning') {
            logger()->warning('Storage usage alert', [
                'status' => $status,
                'usage' => $data,
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function performAndReportCleanup(StorageUsage $usage): array
    {
        $usageBefore = $usage->toArray();
        $cleanupResults = $this->performCleanup($this->config['cleanup_priority'] ?? []);
        $usageAfter = $this->monitorStorageUsage()->toArray();

        return [
            'cleanup_performed' => true,
            'reason' => 'Storage usage threshold exceeded',
            'usage_before' => $usageBefore,
            'usage_after' => $usageAfter,
            'cleanup_results' => $cleanupResults,
        ];
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @param  array<int, array<string, string>>  $recommendations
     */
    private function addDirectoryRecommendations(?array $breakdown, array &$recommendations): void
    {
        if ($breakdown === null || $breakdown === []) {
            return;
        }

        $largestDir = array_key_first($breakdown);
        if (is_string($largestDir) && $largestDir !== 'other') {
            $recommendations[] = [
                'type' => 'directory',
                'recommendation' => "The {$largestDir} directory is the largest. Consider cleaning it up.",
            ];
        }
    }

    /**
     * @param  array<int, array<string, string>>  $recommendations
     */
    private function addUsageRecommendations(StorageUsage $usage, array &$recommendations): void
    {
        if ($usage->status === 'critical') {
            $recommendations[] = [
                'type' => 'critical',
                'recommendation' => 'Storage usage is critical. Immediate cleanup required.',
            ];
        } elseif ($usage->status === 'warning') {
            $recommendations[] = [
                'type' => 'warning',
                'recommendation' => 'Storage usage is high. Consider cleaning up files.',
            ];
        }
    }

    private function handleUpdateError(Exception $e, array $limits): bool
    {
        $this->logger->error('Failed to update storage limits', [
            'error' => $e->getMessage(),
            'limits' => $limits,
        ]);

        return false;
    }

    private function updateConfigFile(): void
    {
        $configPath = config_path('storage_management.php');
        $content = '<?php return '.var_export($this->config, true).';';
        file_put_contents($configPath, $content);
    }

    /**
     * @param  array<string, StorageBreakdown>  $breakdown
     * @param  array{total_files: int, oldest_file: ?string, newest_file: ?string}  $fileStats
     */
    private function buildStorageStatisticsResponse(StorageUsage $usage, array $breakdown, array $fileStats): StorageStatistics
    {
        return new StorageStatistics(
            usage: $usage->toArray(),
            breakdown: $breakdown,
            recommendations: $this->getStorageRecommendations(),
            fileStats: $fileStats,
            config: $this->config
        );
    }

    /**
     * Build monitoring result as a structured data object.
     */
    private function buildMonitoringResult(int $totalSize, int $maxSize, float $usagePercentage, string $status): StorageUsage
    {
        $breakdown = [];
        foreach ($this->getBreakdownDirectories() as $name => $path) {
            if (! is_string($name) || ! is_string($path)) {
                continue;
            }
            if (! is_dir($path)) {
                continue;
            }

            $size = $this->getDirectorySize($path);
            $breakdown[$name] = [
                'size_mb' => round($size / 1024 / 1024, 2),
                'size_bytes' => $size,
                'path' => $path,
            ];
        }

        return new StorageUsage(
            currentSizeMb: round($totalSize / 1024 / 1024, 2),
            maxSizeMb: round($maxSize / 1024 / 1024, 2),
            usagePercentage: round($usagePercentage, 2),
            status: $status,
            needsCleanup: $totalSize > $maxSize,
            breakdown: $breakdown,
        );
    }

    /**
     * Return maximum allowed storage size in bytes from config.
     */
    private function getMaxStorageSize(): int
    {
        $maxMb = (float) ($this->config['max_storage_size_mb'] ?? 1024.0);

        return (int) round($maxMb * 1024 * 1024);
    }

    /**
     * Determine if a cleanup should be performed based on usage and config.
     */
    private function isCleanupNeeded(StorageUsage $usage): bool
    {
        if (! ($this->config['auto_cleanup'] ?? true)) {
            return false;
        }

        return $usage->needsCleanup || $usage->status !== 'healthy';
    }

    /**
     * Execute compression across selected directories.
     *
     * @return array{files_compressed: int, space_saved_mb: float}
     */
    private function executeCompression(): array
    {
        $targets = [
            'logs' => storage_path('logs'),
            'temp' => storage_path('app/temp'),
        ];

        $total = ['files_compressed' => 0, 'space_saved_mb' => 0.0];

        foreach ($targets as $path) {
            if (! is_dir($path)) {
                continue;
            }
            $result = $this->compressFilesInDirectory($path);
            $total['files_compressed'] += $result['files_compressed'];
            $total['space_saved_mb'] += $result['space_saved_mb'];
        }

        return $total;
    }

    /**
     * Execute archival across selected directories.
     *
     * @return array{files_archived: int, archives_created: int, space_saved_mb: float}
     */
    private function executeArchival(): array
    {
        $archivesDir = storage_path('app/archives');
        if (! is_dir($archivesDir)) {
            mkdir($archivesDir, 0o755, true);
        }

        $total = ['files_archived' => 0, 'archives_created' => 0, 'space_saved_mb' => 0.0];
        $targets = [
            'logs' => storage_path('logs'),
            'backups' => storage_path('backups'),
        ];

        foreach ($targets as $name => $path) {
            if (! is_dir($path)) {
                continue;
            }

            $result = $this->createArchiveAndGetResult($path, (string) $name);
            $total['files_archived'] += $result['files_archived'];
            $total['archives_created'] += $result['archives_created'];
            $total['space_saved_mb'] += $result['space_saved_mb'];

            // Remove original directory to reclaim space after archiving
            $this->removeDirectory($path);
            mkdir($path, 0o755, true);
        }

        return $total;
    }

    /**
     * Sort helper for directories by size descending.
     *
     * @param  array<string, mixed>  $a
     * @param  array<string, mixed>  $b
     */
    private static function sortDirectoriesBySize(array $a, array $b): int
    {
        $aSize = isset($a['size_bytes']) && is_int($a['size_bytes']) ? $a['size_bytes'] : 0;
        $bSize = isset($b['size_bytes']) && is_int($b['size_bytes']) ? $b['size_bytes'] : 0;

        return $bSize <=> $aSize;
    }

    private function compressFilesInDirectory(string $directory): array
    {
        $files = $this->getFilesInDirectory($directory);
        $results = ['files_compressed' => 0, 'space_saved_mb' => 0];

        foreach ($files as $file) {
            if (is_string($file)) {
                $this->compressFile($file, $results);
            }
        }

        return $results;
    }

    /**
     * @param  array{files_compressed: int, space_saved_mb: float}  $results
     */
    private function compressFile(string $file, array &$results): void
    {
        $originalSize = filesize($file);
        if ($originalSize === false) {
            return;
        }

        $gzFile = $file.'.gz';
        $fpOut = gzopen($gzFile, 'wb9');
        if ($fpOut === false) {
            return;
        }

        $fpIn = fopen($file, 'rb');
        if ($fpIn === false) {
            gzclose($fpOut);

            return;
        }

        while (! feof($fpIn)) {
            $string = fread($fpIn, 4096);
            if ($string === false) {
                continue;
            }
            gzwrite($fpOut, $string);
        }

        fclose($fpIn);
        gzclose($fpOut);

        $compressedSize = filesize($gzFile);
        if ($compressedSize === false) {
            return;
        }

        $results['files_compressed']++;
        $results['space_saved_mb'] += ($originalSize - $compressedSize) / 1024 / 1024;

        unlink($file);
    }

    /**
     * @param  array<string, mixed>  $breakdown
     * @return array{total_files: int, oldest_file: ?string, newest_file: ?string}
     */
    private function getFileStats(?array $breakdown): array
    {
        $totalFiles = 0;
        $oldestFile = null;
        $newestFile = null;

        if (is_array($breakdown)) {
            foreach ($breakdown as $data) {
                $this->processDirectoryStats($data, $totalFiles, $oldestFile, $newestFile);
            }
        }

        return [
            'total_files' => $totalFiles,
            'oldest_file' => $oldestFile ? date('Y-m-d H:i:s', $oldestFile) : null,
            'newest_file' => $newestFile ? date('Y-m-d H:i:s', $newestFile) : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function processDirectoryStats(array $data, int &$totalFiles, ?int &$oldestFile, ?int &$newestFile): void
    {
        if (! is_array($data) || ! isset($data['path']) || ! is_string($data['path'])) {
            return;
        }

        $files = $this->getFilesInDirectory($data['path']);
        $totalFiles += count($files);

        $this->updateFileTimeStats($files, $oldestFile, $newestFile);
    }

    /**
     * @param  list<string>  $files
     */
    private function updateFileTimeStats(array $files, ?int &$oldestFile, ?int &$newestFile): void
    {
        foreach ($files as $file) {
            if (! is_string($file)) {
                continue;
            }
            $fileTime = filemtime($file);
            if ($fileTime === false) {
                continue;
            }

            $this->updateMinMaxFileTime($fileTime, $oldestFile, $newestFile);
        }
    }

    /**
     * @param-out int $oldestFile
     * @param-out int $newestFile
     */
    private function updateMinMaxFileTime(int $fileTime, ?int &$oldestFile, ?int &$newestFile): void
    {
        if ($oldestFile === null || $fileTime < $oldestFile) {
            $oldestFile = $fileTime;
        }

        if ($newestFile === null || $fileTime > $newestFile) {
            $newestFile = $fileTime;
        }
    }

    /**
     * @return array{files_archived: int, archives_created: int, space_saved_mb: float}
     */
    private function createArchiveAndGetResult(string $directory, string $name): array
    {
        $archivePath = storage_path("app/archives/{$name}_".date('Y-m-d').'.zip');
        $this->createArchive($directory, $archivePath);

        return [
            'files_archived' => count($this->getFilesInDirectory($directory)),
            'archives_created' => 1,
            'space_saved_mb' => $this->getDirectorySize($directory) / 1024 / 1024,
        ];
    }

    private function createArchive(string $directory, string $archivePath): void
    {
        $zip = new ZipArchive;

        if ($zip->open($archivePath, ZipArchive::CREATE) !== true) {
            throw new Exception("Cannot open <{$archivePath}> for writing");
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr((string) $filePath, strlen($directory) + 1);
                $zip->addFile($filePath, $relativePath);
            }
        }

        $zip->close();
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function getStorageStatus(float $usagePercentage): string
    {
        $criticalThreshold = $this->config['critical_threshold'] ?? 95;
        if ($usagePercentage >= $criticalThreshold) {
            return 'critical';
        }

        if ($usagePercentage >= ($this->config['warning_threshold'] ?? 80)) {
            return 'warning';
        }

        return 'healthy';
    }

    private function getBreakdownDirectories(): array
    {
        return [
            'logs' => storage_path('logs'),
            'cache' => storage_path('framework/cache'),
            'sessions' => storage_path('framework/sessions'),
            'views' => storage_path('framework/views'),
            'temp' => storage_path('app/temp'),
            'backups' => storage_path('backups'),
            'uploads' => storage_path('app/public/uploads'),
            'other' => storage_path('app'),
        ];
    }

    /**
     * Auto cleanup if needed.
     *
     * @return array{
     *     deleted_files: int,
     *     space_freed_mb: float,
     *     errors: list<string>
     * }
     */
    private function performCleanup(array $priority): array
    {
        $cleanupResults = [];

        foreach ($priority as $type) {
            $this->executeCleanupType($type, $cleanupResults);

            if ($this->isStorageUsageHealthy()) {
                break;
            }
        }

        return $cleanupResults;
    }

    /**
     * @param array<string, array{
     *     deleted_files: int,
     *     space_freed_mb: float,
     *     errors: list<string>
     * }> $cleanupResults
     */
    private function executeCleanupType(string $type, array &$cleanupResults): void
    {
        $cleanupMethod = 'cleanup'.ucfirst($type).'Files';
        if (method_exists($this->cleanupService, $cleanupMethod)) {
            $cleanupResults[$type] = $this->cleanupService->{$cleanupMethod}();
        }
    }

    private function isStorageUsageHealthy(): bool
    {
        return $this->monitorStorageUsage()->status === 'healthy';
    }

    /**
     * Remove directory.
     *
     * @return list<string>
     */
    private function getFilesInDirectory(string $directory): array
    {
        $files = [];

        if (is_dir($directory)) {
            $iterator = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file instanceof \SplFileInfo && $file->isFile()) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    /**
     * Remove directory recursively.
     */
    private function removeDirectory(string $directory): void
    {
        if (! is_dir($directory)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $this->deleteFileOrDirectory($file);
            }
        }

        rmdir($directory);
    }

    private function deleteFileOrDirectory(\SplFileInfo $file): void
    {
        if ($file->isDir()) {
            rmdir($file->getPathname());
        } else {
            unlink($file->getPathname());
        }
    }

    /**
     * Get directory size.
     */
    private function getDirectorySize(string $directory): int
    {
        if (! is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $size += $this->getFileSize($file);
            }
        }

        return $size;
    }

    private function getFileSize(\SplFileInfo $file): int
    {
        return $file->isFile() ? $file->getSize() : 0;
    }

    private function executeStorageOperation(string $operationName, callable $executor, array $initialResult): array
    {
        $configKey = strtolower($operationName).'_enabled';
        if (! ($this->config[$configKey] ?? false)) {
            return [strtolower($operationName).'_disabled' => true];
        }

        try {
            $results = $executor();
            $this->logger->info("File {$operationName} completed", $results);

            return $results;
        } catch (Exception $e) {
            $error = ['error' => $e->getMessage()];
            $this->logger->error("File {$operationName} failed", $error);

            return array_merge($initialResult, ['errors' => [$e->getMessage()]]);
        }
    }
}
