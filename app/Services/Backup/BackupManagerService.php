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
    private BackupValidatorService $validatorService;

    private BackupCompressionService $compressionService;

    private BackupDatabaseService $databaseService;

    private BackupFileSystemService $fileSystemService;

    private BackupConfigurationService $configurationService;

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
     * @return array{path: string, size: int, manifest: array}
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
            $manifest = $this->executeBackupComponents($backupDir, $type, $options);
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
     * @return array{components: array, status: string}
     *
     * @throws Exception
     */
    public function restoreBackup(string $backupPath, array $options): array
    {
        $this->validatorService->validateRestoreParameters($backupPath, $options);

        $extractDir = $this->prepareRestoreDirectory($backupPath);

        try {
            $manifest = $this->readBackupManifest($extractDir);
            $this->validatorService->validateBackupManifest($manifest);

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
     * @return array{components: array, size: int, status: string}
     *
     * @throws Exception
     */
    private function executeBackupComponents(string $backupDir, string $type, array $options): array
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
                if ($strategy) {
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
        $backupDir = storage_path('app/backups/'.$name);

        if (is_dir($backupDir)) {
            throw new Exception("Backup directory already exists: {$backupDir}");
        }

        if (! mkdir($backupDir, 0755, true)) {
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
     * Delete directory recursively.
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
            if ($file instanceof \SplFileInfo) {
                if ($file->isDir()) {
                    rmdir($file->getPathname());
                } else {
                    unlink($file->getPathname());
                }
            }
        }

        rmdir($dir);
    }
}
