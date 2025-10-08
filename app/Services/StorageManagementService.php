<?php

declare(strict_types=1);

namespace App\Services;

use App\DataObjects\StorageUsage;
use Carbon\Carbon;
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
     *
     * @return array<string, array{size_mb: float, size_bytes: int, path: string}>
     */
    public function monitorStorageUsage(): StorageUsage
    {
        $totalSize = $this->getDirectorySize(storage_path());
        $maxSize = $this->getMaxStorageSize();
        $usagePercentage = $maxSize > 0 ? ($totalSize / $maxSize) * 100 : 0;
        $status = $this->getStorageStatus($usagePercentage);

        $result = $this->buildMonitoringResult($totalSize, $maxSize, $usagePercentage, $status);

        $this->logStorageUsage($status, $result->toArray());

        return $result;
    }

    private function logStorageUsage(string $status, array $data): void
    {
        if ($status === 'critical' || $status === 'warning') {
            Log::warning('Storage usage alert', [
                'status' => $status,
                'usage' => $data,
            ]);
        }
    }

    /**
     * Get storage breakdown by directory.
     *
     * @return array<string, StorageBreakdown>}
     */
    public function getStorageBreakdown(): array
    {
        $breakdown = [];
        $directories = $this->getBreakdownDirectories();

        foreach ($directories as $name => $path) {
            if (is_dir($path)) {
                $size = $this->getDirectorySize($path);
                $breakdown[$name] = new StorageBreakdown(
                    size_mb: round($size / 1024 / 1024, 2),
                    size_bytes: $size,
                    path: $path,
                );
            }
        }

        return $breakdown;
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
     * @return array<string, mixed>
     */
    private function performAndReportCleanup(StorageUsage $usage): array
    {
        $usageBefore = $usage->toArray();
        $cleanupResults = $this->performCleanup();
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
        return $this->executeStorageOperation('compression', function () {
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
        return $this->executeStorageOperation('archival', function () {
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
     * @param  array<string, mixed>  $breakdown
     * @param  array<int, array<string, string>>  $recommendations
     */
    private function addDirectoryRecommendations(?array $breakdown, array &$recommendations): void
    {
        if (empty($breakdown)) {
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
     * Get storage statistics.
     */
    public function getStorageStatistics(): StorageStatistics
    {
        $usage = $this->monitorStorageUsage();
        /** @var array<string, StorageBreakdown> $breakdown */
        $breakdown = $usage->breakdown;

        $fileStats = $this->getFileStats($breakdown);

        return $this->buildStorageStatisticsResponse($usage, $breakdown, $fileStats);
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
            file_stats: $fileStats,
            config: $this->config
        );
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

        foreach ($files as $name => $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($directory) + 1);
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

        foreach ($files as $name => $file) {
            if (! $file->isDir()) {
                $filePath = $file->getRealPath();
                $relativePath = substr($filePath, strlen($directory) + 1);
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

    private function compressFile(string $file): int
    {
        $originalSize = filesize($file);
        if ($originalSize === false) {
            return 0;
        }

        $compressedFile = $file.'.gz';
        $fileContent = file_get_contents($file);
        if ($fileContent === false) {
            return 0;
        }

        $compressedContent = gzencode($fileContent);
        if ($compressedContent === false) {
            return 0;
        }

        if ($this->isDryRun) {
            $this->logger->info(
                '[Dry Run] Skipped writing compressed file.',
                [
                    'file' => $file,
                    'compressed_file' => $compressedFile,
                ]
            );

            return 0;
        }

        $bytesWritten = file_put_contents($compressedFile, $compressedContent);
        if ($bytesWritten === false) {
            return 0;
        }

        unlink($file);

        return $originalSize - $bytesWritten;
    }

    /**
     * Compress files in a directory.
     *
     * @return array{files_compressed: int, space_saved_mb: float}
     */
    private function compressFilesInDirectory(string $directory): array
    {
        $filesCompressed = 0;
        $spaceSaved = 0;

        $files = glob($directory.'/*');
        if ($files === false) {
            return ['files_compressed' => 0, 'space_saved_mb' => 0];
        }

        foreach ($files as $file) {
            $this->compressFileAndTrackStats($file, $filesCompressed, $spaceSaved);
        }

        return [
            'files_compressed' => $filesCompressed,
            'space_saved_mb' => round($spaceSaved / 1024 / 1024, 2),
        ];
    }

    private function compressFileAndTrackStats(string $file, int &$filesCompressed, int &$spaceSaved): void
    {
        if (is_file($file) && ! str_ends_with($file, '.gz')) {
            $spaceSaved += $this->compressFile($file);
            $filesCompressed++;
        }
    }

    private function createArchiveDirectory(string $archiveDirectory): void
    {
        if (! is_dir($archiveDirectory)) {
            mkdir($archiveDirectory, 0755, true);
        }
    }

    private function executeArchiveCommand(string $archivePath, string $directory): int
    {
        $command = "tar -czf {$archivePath} -C ".dirname($directory).' '.basename($directory);
        exec($command, $output, $returnCode);

        return $returnCode;
    }

    private function getArchivePath(string $name): string
    {
        $archiveName = $name.'_'.Carbon::now()->format('Y-m-d').'.tar.gz';

        return storage_path('archives/'.$archiveName);
    }

    /**
     * Create archive.
     *
     * @return array{files_archived: int, archives_created: int, space_saved_mb: float}
     */
    private function createArchive(string $directory, string $name): array
    {
        $archivePath = $this->getArchivePath($name);

        $this->createArchiveDirectory(dirname($archivePath));

        $returnCode = $this->executeArchiveCommand($archivePath, $directory);

        if ($returnCode === 0) {
            return $this->handleSuccessfulArchive($directory, $archivePath);
        }

        return [
            'files_archived' => 0,
            'archives_created' => 0,
            'space_saved_mb' => 0,
        ];
    }

    private function handleSuccessfulArchive(string $directory, string $archivePath): array
    {
        $originalSize = $this->getDirectorySize($directory);
        $archiveSize = filesize($archivePath);
        $spaceSaved = $originalSize - ($archiveSize ?: 0);

        $files = glob($directory.'/*') ?: [];

        if ($this->isDryRun) {
            $this->logger->info(
                '[Dry Run] Skipped deletion of files in directory.',
                [
                    'directory' => $directory,
                    'files_to_delete' => $files,
                ]
            );
        } else {
            // Remove original directory
            $this->removeDirectory($directory);
        }

        return [
            'files_archived' => count($files),
            'archives_created' => 1,
            'space_saved_mb' => round($spaceSaved / 1024 / 1024, 2),
        ];
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
