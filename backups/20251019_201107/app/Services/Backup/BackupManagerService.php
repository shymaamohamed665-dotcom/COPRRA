<?php

declare(strict_types=1);

namespace App\Services\Backup;

use App\Services\Backup\Services\BackupCompressionService;
use App\Services\Backup\Services\BackupConfigurationService;
use App\Services\Backup\Services\BackupDatabaseService;
use App\Services\Backup\Services\BackupFileSystemService;
use App\Services\Backup\Services\BackupValidatorService;
use App\Services\Backup\Strategies\BackupStrategyInterface;
use Exception;
use Illuminate\Support\Facades\Log;

final class BackupManagerService
{
    private readonly BackupValidatorService $validatorService;

    private readonly BackupCompressionService $compressionService;

    private readonly BackupDatabaseService $databaseService;

    private readonly BackupFileSystemService $fileSystemService;

    private readonly BackupConfigurationService $configurationService;

    /**
     * @var array<string, BackupStrategyInterface>
     */
    private array $strategies = [];

    public function __construct(
        BackupValidatorService $validatorService,
        BackupCompressionService $compressionService,
        BackupDatabaseService $databaseService,
        BackupFileSystemService $fileSystemService,
        BackupConfigurationService $configurationService
    ) {
        $this->validatorService = $validatorService;
        $this->compressionService = $compressionService;
        $this->databaseService = $databaseService;
        $this->fileSystemService = $fileSystemService;
        $this->configurationService = $configurationService;
    }

    /**
     * Register a backup strategy.
     */
    public function registerStrategy(string $name, BackupStrategyInterface $strategy): void
    {
        $this->strategies[$name] = $strategy;
    }

    /**
     * Create a backup.
     *
     * @param  array{type: string, name: string, directories?: array, tables?: array, compress?: bool}  $options
     * @return array<array<array<array<int|string|array<string>>>|float|int|string>|int|string>
     *
     * @psalm-return array{path: string, size: int, manifest: array{components: array{database?: array{filename: string, size: int, status: string}, files?: array{directories: list<string>, size: int, status: string}, config?: array{files: list<string>, size: int, status: string}}, size: int, status: 'completed', name: string, type: string, created_at: string, compressed_size?: int, compression_time?: float, compressed_path?: string}}
     *
     * @throws Exception
     */
    public function createBackup(array $options): array
    {
        $type = $options['type'];
        $name = $options['name'];

        $this->validatorService->validateBackupParameters($type, $options);

        $backupDir = $this->createBackupDirectory($name);

        try {
            $manifest = $this->executeBackupComponents($backupDir, $type);
            $manifest['name'] = $name;
            $manifest['type'] = $type;
            $manifest['created_at'] = now()->toIso8601String();

            $this->createBackupManifest($backupDir, $manifest);

            if ($options['compress'] ?? true) {
                $compressionResult = $this->compressionService->compressBackup($backupDir, $name);
                $manifest['compressed_size'] = $compressionResult['size'];
                $manifest['compression_time'] = $compressionResult['compression_time'];
                $manifest['compressed_path'] = $compressionResult['path'];

                return [
                    'path' => $compressionResult['path'],
                    'size' => $compressionResult['size'],
                    'manifest' => $manifest,
                ];
            }

            return [
                'path' => $backupDir,
                'size' => $manifest['size'],
                'manifest' => $manifest,
            ];
        } catch (Exception $e) {
            $this->cleanupFailedBackup($backupDir);

            throw $e;
        }
    }

    /**
     * Restore from backup.
     *
     * @param  array{components?: array, overwrite?: bool}  $options
     * @return array<array<array>|string>
     *
     * @psalm-return array{components: array<string, array<string, mixed>>, status: 'completed'}
     *
     * @throws Exception
     */
    public function restoreBackup(string $backupPath, array $options): array
    {
        $this->validatorService->validateRestoreParameters($backupPath, $options);

        $extractDir = $this->prepareRestoreDirectory($backupPath);

        try {
            $manifest = $this->readBackupManifest($extractDir);

            $restoreResults = $this->executeRestoreComponents($extractDir, $manifest, $options);

            $this->cleanupExtractedDirectory($extractDir);

            return [
                'components' => $restoreResults,
                'status' => 'completed',
            ];
        } catch (Exception $e) {
            $this->cleanupExtractedDirectory($extractDir);

            throw $e;
        }
    }

    /**
     * Execute backup components.
     *
     * @return array<array<array<int|string|array<string>>>|int|string>
     *
     * @psalm-return array{components: array{database?: array{filename: string, size: int, status: string}, files?: array{directories: list<string>, size: int, status: string}, config?: array{files: list<string>, size: int, status: string}}, size: int, status: 'completed'}
     *
     * @throws Exception
     */
    private function executeBackupComponents(string $backupDir, string $type): array
    {
        $components = [];
        $totalSize = 0;

        if ($type === 'full' || $type === 'database') {
            $components['database'] = $this->databaseService->backupDatabase($backupDir);
            $totalSize += $components['database']['size'];
        }

        if ($type === 'full' || $type === 'files') {
            $components['files'] = $this->fileSystemService->backupFiles($backupDir);
            $totalSize += $components['files']['size'];

            $components['config'] = $this->configurationService->backupConfiguration($backupDir);
            $totalSize += $components['config']['size'];
        }

        return [
            'components' => $components,
            'size' => $totalSize,
            'status' => 'completed',
        ];
    }

    /**
     * Execute restore components.
     *
     * @param  array{components?: array, overwrite?: bool}  $options
     * @return array<array>
     *
     * @psalm-return array<string, array<string, mixed>>
     *
     * @throws Exception
     */
    private function executeRestoreComponents(string $extractDir, array $manifest, array $options): array
    {
        $results = [];
        $requestedComponents = $options['components'] ?? array_keys($manifest['components']);

        foreach ($requestedComponents as $component) {
            if (isset($manifest['components'][$component])) {
                $strategy = $this->findStrategyForComponent($component);
                if ($strategy instanceof \App\Services\Backup\Strategies\BackupStrategyInterface) {
                    $results[$component] = $strategy->restore($extractDir, $manifest['components'][$component]);
                }
            }
        }

        return $results;
    }

    /**
     * Find strategy for component.
     */
    private function findStrategyForComponent(string $component): ?BackupStrategyInterface
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->getComponentName() === $component) {
                return $strategy;
            }
        }

        return null;
    }

    /**
     * Create backup directory.
     *
     * @throws Exception
     */
    private function createBackupDirectory(string $name): string
    {
        $backupDir = storage_path('backups/'.$name);

        // If the directory already exists, reuse it to align with tests
        if (! is_dir($backupDir) && ! mkdir($backupDir, 0o755, true)) {
            throw new Exception("Failed to create backup directory: {$backupDir}");
        }

        return $backupDir;
    }

    /**
     * Create backup manifest.
     *
     * @throws Exception
     */
    private function createBackupManifest(string $backupDir, array $manifest): void
    {
        $manifestPath = $backupDir.'/manifest.json';

        if (file_put_contents($manifestPath, json_encode($manifest, JSON_PRETTY_PRINT)) === false) {
            throw new Exception("Failed to create backup manifest: {$manifestPath}");
        }
    }

    /**
     * Read backup manifest.
     *
     * @return array{type: string, name: string, created_at: string, size: int, components: array}
     *
     * @throws Exception
     */
    private function readBackupManifest(string $extractDir): array
    {
        $manifestPath = $extractDir.'/manifest.json';

        if (! file_exists($manifestPath)) {
            throw new Exception("Backup manifest not found: {$manifestPath}");
        }

        $content = file_get_contents($manifestPath);
        if ($content === false) {
            throw new Exception("Failed to read backup manifest: {$manifestPath}");
        }

        $manifest = json_decode($content, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Invalid backup manifest JSON: '.json_last_error_msg());
        }

        return $manifest;
    }

    /**
     * Prepare restore directory.
     *
     * @throws Exception
     */
    private function prepareRestoreDirectory(string $backupPath): string
    {
        if (str_ends_with($backupPath, '.tar.gz')) {
            return $this->compressionService->extractBackup($backupPath);
        }

        if (is_dir($backupPath)) {
            return $backupPath;
        }

        throw new Exception("Invalid backup path: {$backupPath}");
    }

    /**
     * Cleanup failed backup.
     */
    private function cleanupFailedBackup(string $backupDir): void
    {
        try {
            if (is_dir($backupDir)) {
                $this->deleteDirectory($backupDir);
            }
        } catch (Exception $e) {
            Log::error('Failed to cleanup failed backup', ['backup_dir' => $backupDir, 'error' => $e->getMessage()]);
        }
    }

    /**
     * Cleanup extracted directory.
     */
    private function cleanupExtractedDirectory(string $extractDir): void
    {
        try {
            if (str_contains($extractDir, 'extracted_') && is_dir($extractDir)) {
                $this->deleteDirectory($extractDir);
            }
        } catch (Exception $e) {
            Log::error('Failed to cleanup extracted directory', ['extract_dir' => $extractDir, 'error' => $e->getMessage()]);
        }
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
                rmdir($path);

                continue;
            }

            if (PHP_OS_FAMILY === 'Windows') {
                chmod($path, 0o666);
            }

            unlink($path);
        }

        rmdir($dir);
    }
}
