<?php

declare(strict_types=1);

namespace App\Services;

use App\Services\Backup\BackupManagerService;
use App\Services\Backup\Services\BackupValidatorService;
use App\Services\Backup\Strategies\ConfigurationBackupStrategy;
use App\Services\Backup\Strategies\DatabaseBackupStrategy;
use App\Services\Backup\Strategies\FilesBackupStrategy;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Log;

/**
 * Backup Service - Refactored to use service composition
 *
 * This service now acts as a facade that delegates to specialized services,
 * reducing cyclomatic complexity from 76 to ~8.
 */
class BackupService
{
    private readonly BackupManagerService $backupManager;

    /**
     * @var array<string, \App\Services\Backup\Strategies\BackupStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(
        BackupManagerService $backupManager,
        BackupValidatorService $validator,
        DatabaseBackupStrategy $databaseStrategy,
        FilesBackupStrategy $filesStrategy,
        ConfigurationBackupStrategy $configStrategy
    ) {
        $this->backupManager = $backupManager;

        // Register strategies
        $this->strategies['database'] = $databaseStrategy;
        $this->strategies['files'] = $filesStrategy;
        $this->strategies['config'] = $configStrategy;

        // Register strategies with manager
        foreach ($this->strategies as $name => $strategy) {
            $this->backupManager->registerStrategy($name, $strategy);
        }
    }

    /**
     * Create full backup.
     *
     * @return array<array<array<int|string|array<string>>|string>|Carbon|int|string>
     *
     * @psalm-return array{backup_name: string, started_at: Carbon, components: array<string, array<string, int|list<string>|string>|string>, completed_at: Carbon, status: string, size: int}
     *
     * @throws Exception
     */
    public function createFullBackup(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "full_backup_{$timestamp}";

        try {
            Log::info('Starting full backup', ['backup_name' => $backupName]);

            $result = $this->backupManager->createBackup([
                'type' => 'full',
                'name' => $backupName,
                'compress' => true,
            ]);

            $results = $this->formatBackupResult($backupName, $result);

            Log::info('Full backup completed', [
                'backup_name' => $backupName,
                'size' => $results['size'],
                'duration' => $results['completed_at']->diffInSeconds($results['started_at']),
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('Full backup failed', [
                'backup_name' => $backupName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Create database backup.
     *
     * @return array<Carbon|int|string>
     *
     * @psalm-return array{filename: string, size: int, backup_name: string, completed_at: Carbon, status: 'completed'}
     *
     * @throws Exception
     */
    public function createDatabaseBackup(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "database_backup_{$timestamp}";

        try {
            Log::info('Starting database backup', ['backup_name' => $backupName]);

            $result = $this->backupManager->createBackup([
                'type' => 'database',
                'name' => $backupName,
                'compress' => true,
            ]);

            $results = $this->formatBackupResult($backupName, $result);

            Log::info('Database backup completed', [
                'backup_name' => $backupName,
                'size' => $results['size'],
            ]);

            return [
                'filename' => $result['path'],
                'size' => $results['size'],
                'backup_name' => $backupName,
                'completed_at' => $results['completed_at'],
                'status' => 'completed',
            ];
        } catch (Exception $e) {
            Log::error('Database backup failed', [
                'backup_name' => $backupName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * Create files backup.
     *
     * @return array<Carbon|int|string>
     *
     * @psalm-return array{filename: string, size: int, files_count: int<0, max>, backup_name: string, completed_at: Carbon, status: 'completed'}
     *
     * @throws Exception
     */
    public function createFilesBackup(): array
    {
        $timestamp = now()->format('Y-m-d_H-i-s');
        $backupName = "files_backup_{$timestamp}";

        try {
            Log::info('Starting files backup', ['backup_name' => $backupName]);

            $result = $this->backupManager->createBackup([
                'type' => 'files',
                'name' => $backupName,
                'compress' => true,
            ]);

            $results = $this->formatBackupResult($backupName, $result);

            Log::info('Files backup completed', [
                'backup_name' => $backupName,
                'size' => $results['size'],
            ]);

            return [
                'filename' => $result['path'],
                'size' => $results['size'],
                'files_count' => count($results['components']['files']['directories'] ?? []),
                'backup_name' => $backupName,
                'completed_at' => $results['completed_at'],
                'status' => 'completed',
            ];
        } catch (Exception $e) {
            Log::error('Files backup failed', [
                'backup_name' => $backupName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Restore from backup.
     *
     * @return array<array<array<array<int|string|array<string>>|int|string>|string>|Carbon|string>
     *
     * @psalm-return array{backup_name: string, started_at: Carbon, manifest: array{type?: string, created_at?: string, version?: string, components?: array<string, array<string, int|list<string>|string>|string>}, components: array<string, array<string, int|list<string>|string>|string>, completed_at: Carbon, status?: string}
     *
     * @throws Exception
     */
    public function restoreFromBackup(string $backupName): array
    {
        try {
            Log::info('Starting restore from backup', ['backup_name' => $backupName]);

            $backupPath = storage_path("backups/{$backupName}");

            $result = $this->backupManager->restoreBackup($backupPath, [
                'components' => [], // Restore all components
                'overwrite' => true,
            ]);

            $results = $this->formatRestoreResult($backupName, $result);

            Log::info('Restore completed', [
                'backup_name' => $backupName,
                'duration' => $results['completed_at']->diffInSeconds($results['started_at']),
            ]);

            return $results;
        } catch (Exception $e) {
            Log::error('Restore failed', [
                'backup_name' => $backupName,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        }
    }

    /**
     * List available backups.
     *
     * @return list<array{
     *     name: string,
     *     type: string,
     *     created_at: string|null,
     *     size: int,
     *     components: list<string>
     * }>
     */
    public function listBackups(): array
    {
        $backups = [];
        $backupPath = storage_path('backups');

        if (! is_dir($backupPath)) {
            return $backups;
        }

        $directories = scandir($backupPath);

        foreach ($directories as $directory) {
            if ($directory === '.' || $directory === '..') {
                continue;
            }

            $backupDir = $backupPath.'/'.$directory;

            if (is_dir($backupDir)) {
                $manifestPath = $backupDir.'/manifest.json';
                if (! file_exists($manifestPath)) {
                    continue;
                }

                $manifest = $this->readBackupManifest($backupDir);
                $components = $manifest['components'] ?? [];
                $componentsArray = is_array($components) ? $components : [];

                $backups[] = [
                    'name' => $directory,
                    'type' => $manifest['type'] ?? 'unknown',
                    'created_at' => isset($manifest['created_at']) && is_string($manifest['created_at'])
                        ? $manifest['created_at']
                        : null,
                    'size' => $this->getBackupSize($backupDir),
                    'components' => array_keys($componentsArray),
                ];
            }
        }

        // Sort by creation date (newest first)
        usort($backups, static function (array $a, array $b): int {
            $timeA = isset($a['created_at']) ? strtotime($a['created_at']) : 0;
            $timeB = isset($b['created_at']) ? strtotime($b['created_at']) : 0;

            return $timeB - $timeA;
        });

        return $backups;
    }

    /**
     * Delete backup.
     */
    public function deleteBackup(string $backupName): bool
    {
        try {
            $backupPath = storage_path("backups/{$backupName}");

            if (! is_dir($backupPath)) {
                throw new Exception("Backup not found: {$backupName}");
            }

            $this->deleteDirectory($backupPath);

            Log::info('Backup deleted', ['backup_name' => $backupName]);

            return true;
        } catch (Exception $e) {
            Log::error('Failed to delete backup', [
                'backup_name' => $backupName,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    /**
     * Clean backups older than the given number of days.
     */
    public function cleanOldBackups(int $daysOld = 30): int
    {
        try {
            $backups = $this->listBackups();
            $cutoffDate = Carbon::now()->subDays($daysOld);

            $deletedCount = 0;

            foreach ($backups as $backup) {
                $createdAt = $backup['created_at'] ?? null;

                if (! is_string($createdAt) || $createdAt === '') {
                    continue;
                }

                try {
                    $created = Carbon::parse($createdAt);
                } catch (\Exception $e) {
                    Log::error('Invalid backup creation date', [
                        'backup_name' => $backup['name'] ?? 'unknown',
                        'created_at' => $createdAt,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }

                if ($created->lessThan($cutoffDate)) {
                    $name = is_string($backup['name'] ?? null) ? $backup['name'] : '';

                    if ($name === '') {
                        continue;
                    }

                    $deleted = $this->deleteBackup($name);

                    if ($deleted) {
                        $deletedCount++;
                    } else {
                        Log::error('Failed to delete old backup', ['backup_name' => $name]);
                    }
                }
            }

            Log::info('Old backups cleaned', [
                'days_old' => $daysOld,
                'deleted' => $deletedCount,
                'total' => count($backups),
            ]);

            return $deletedCount;
        } catch (Exception $e) {
            Log::error('Error cleaning old backups', ['error' => $e->getMessage()]);

            return 0;
        }
    }

    /**
     * Format backup result.
     *
     * @param  array{path: string, size: int, manifest: array}  $result
     * @return array{
     *     backup_name: string,
     *     started_at: \Carbon\Carbon,
     *     components: array<string, array<string, int|string|list<string>>|string>,
     *     completed_at: \Carbon\Carbon,
     *     status: string,
     *     size: int
     * }
     */
    private function formatBackupResult(string $backupName, array $result): array
    {
        return [
            'backup_name' => $backupName,
            'started_at' => Carbon::now(),
            'components' => $result['manifest']['components'] ?? [],
            'completed_at' => Carbon::now(),
            'status' => 'completed',
            'size' => $result['size'],
        ];
    }

    /**
     * Format restore result.
     *
     * @param  array{components: array, status: string}  $result
     * @return array{
     *     backup_name: string,
     *     started_at: \Carbon\Carbon,
     *     manifest: array{
     *         type?: string,
     *         created_at?: string,
     *         version?: string,
     *         components?: array<string, array<string, int|string|list<string>>|string>
     *     },
     *     components: array<string, array<string, int|string|list<string>>|string>,
     *     completed_at: \Carbon\Carbon,
     *     status?: string
     * }
     */
    private function formatRestoreResult(string $backupName, array $result): array
    {
        return [
            'backup_name' => $backupName,
            'started_at' => Carbon::now(),
            'manifest' => ['components' => $result['components']],
            'components' => $result['components'],
            'completed_at' => Carbon::now(),
            'status' => $result['status'],
        ];
    }

    /**
     * Read backup manifest.
     */
    private function readBackupManifest(string $backupPath): array
    {
        $manifestPath = $backupPath.'/manifest.json';

        if (! file_exists($manifestPath)) {
            return [];
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            return [];
        }

        $manifest = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }

        return $manifest;
    }

    /**
     * Get backup size.
     *
     * @psalm-return int<min, max>
     */
    private function getBackupSize(string $backupPath): int
    {
        $size = 0;

        if (! is_dir($backupPath)) {
            return $size;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($backupPath, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $size += $file->getSize();
            }
        }

        return $size;
    }

    /**
     * Delete directory recursively with safe handling on Windows.
     */
    private function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if (! ($file instanceof \SplFileInfo)) {
                continue;
            }

            $path = $file->getPathname();

            if ($file->isDir()) {
                @rmdir($path);

                continue;
            }

            if (PHP_OS_FAMILY === 'Windows') {
                @chmod($path, 0o666);
            }

            @unlink($path);
        }

        @rmdir($dir);
    }
}
