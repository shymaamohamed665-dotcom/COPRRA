<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class BackupService
{
    private string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = $backupPath;
    }

    /**
     * Create backup with given configuration.
     *
     * @param  array<string, string>  $config
     * @return array<string, string|array>
     */
    public function createBackup(array $config): array
    {
        try {
            $backupConfig = $this->prepareBackupConfiguration($config);
            $zip = $this->createBackupArchive($backupConfig['filename']);
            $this->populateBackupContent($zip, $backupConfig);

            return $this->finalizeBackup($backupConfig);
        } catch (\Exception $e) {
            Log::error('Error creating backup: '.$e->getMessage());
            throw $e;
        }
    }

    /**
     * Prepare backup configuration from options.
     *
     * @param  array<string, string>  $options
     * @return array<string, string>
     */
    private function prepareBackupConfiguration(array $options): array
    {
        return [
            'type' => $options['type'] ?? 'full',
            'name' => $options['name'] ?? 'backup_'.now()->format('Y-m-d_H-i-s'),
            'description' => $options['description'] ?? '',
            'filename' => $this->generateBackupFilename($options['name'] ?? 'backup'),
        ];
    }

    /**
     * Create backup archive and return zip instance.
     */
    private function createBackupArchive(string $filename): ZipArchive
    {
        $filePath = $this->prepareBackupPath($filename);

        return $this->createZipArchive($filePath);
    }

    /**
     * Populate backup content based on configuration.
     *
     * @param  array<string, string>  $config
     */
    private function populateBackupContent(ZipArchive $zip, array $config): void
    {
        $this->addBackupContent($zip, $config['type'], $config);
        $zip->close();
    }

    /**
     * Finalize backup and return result.
     *
     * @param  array<string, string>  $config
     * @return array<string, string|array>
     */
    private function finalizeBackup(array $config): array
    {
        return $this->createBackupResult($config['filename'], $config['type'], $config['description']);
    }

    /**
     * Generate backup filename.
     */
    private function generateBackupFilename(string $name): string
    {
        return (is_string($name) ? (string) $name : 'backup').'.zip';
    }

    /**
     * Prepare backup directory and file path.
     */
    private function prepareBackupPath(string $filename): string
    {
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0755, true);
        }

        return $this->backupPath.'/'.(string) $filename;
    }

    /**
     * Create and validate zip archive.
     */
    private function createZipArchive(string $filePath): ZipArchive
    {
        $zip = new ZipArchive;
        if ($zip->open($filePath, ZipArchive::CREATE) !== true) {
            throw new RuntimeException('Cannot create backup file');
        }

        return $zip;
    }

    /**
     * Add backup content based on type.
     *
     * @param  array<string, string|array>  $options
     */
    private function addBackupContent(ZipArchive $zip, string $type, array $options): void
    {
        if ($type === 'full' || $type === 'database') {
            $this->backupDatabase($zip);
        }

        if ($type === 'full' || $type === 'files') {
            $this->backupFiles($zip, $options);
        }
    }

    /**
     * Create backup result array.
     *
     * @return array<string, string|int>
     */
    private function createBackupResult(string $filename, string $type, string $description): array
    {
        $filePath = $this->backupPath.'/'.(string) $filename;
        $fileSize = filesize($filePath);

        Log::info('Backup created: '.$filename);

        return [
            'id' => pathinfo($filename, PATHINFO_FILENAME),
            'filename' => $filename,
            'type' => $type,
            'size' => $fileSize !== false ? $fileSize : 0,
            'size_formatted' => $this->formatBytes($fileSize !== false ? $fileSize : 0),
            'created_at' => now()->toISOString(),
            'description' => $description,
        ];
    }

    /**
     * Backup database.
     */
    private function backupDatabase(ZipArchive $zip): void
    {
        try {
            // Create database dump
            $dumpFile = storage_path('app/temp/database_dump.sql');
            $dumpDir = dirname($dumpFile);

            if (! is_dir($dumpDir)) {
                mkdir($dumpDir, 0755, true);
            }

            // Use Laravel's database backup command
            Artisan::call('db:backup', [
                '--path' => $dumpFile,
                '--force' => true,
            ]);

            if (file_exists($dumpFile)) {
                $zip->addFile($dumpFile, 'database_dump.sql');
            }
        } catch (\Exception $e) {
            Log::error('Error backing up database: '.$e->getMessage());
        }
    }

    /**
     * Backup files.
     *
     * @param  array<string, string|array>  $options
     */
    private function backupFiles(ZipArchive $zip, array $options): void
    {
        try {
            $directories = $options['directories'] ?? [
                'app',
                'config',
                'database',
                'resources',
                'routes',
            ];

            if (is_array($directories)) {
                foreach ($directories as $dir) {
                    if (is_string($dir)) {
                        $this->addDirectoryToZip($zip, base_path($dir), $dir);
                    }
                }
            }
        } catch (\Exception $e) {
            Log::error('Error backing up files: '.$e->getMessage());
        }
    }

    /**
     * Add directory to zip.
     */
    private function addDirectoryToZip(ZipArchive $zip, string $dir, string $zipPath): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );

        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $realPath = $file->getRealPath();
                if ($realPath !== false) {
                    $relativePath = $zipPath.'/'.substr($realPath, strlen($dir) + 1);
                    $zip->addFile($realPath, $relativePath);
                }
            }
        }
    }

    /**
     * Format bytes to human readable format.
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= 1024 ** $pow;

        return round($bytes, 2).' '.$units[$pow];
    }
}
