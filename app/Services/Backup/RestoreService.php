<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class RestoreService
{
    private string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = $backupPath;
    }

    /**
     * Restore from backup.
     *
     * @param  array<string, string|int|null>  $backup
     * @return array<string, string>
     */
    public function restoreFromBackup(array $backup): array
    {
        try {
            $filename = is_string($backup['filename'] ?? '')
            ? (string) ($backup['filename'] ?? '')
            : '';
            $filePath = $this->backupPath.'/'.$filename;

            return $this->performRestore($filePath);
        } catch (\Exception $e) {
            Log::error('Error restoring backup: '.$e->getMessage());

            return [
                'success' => false,
                'message' => 'Failed to restore backup: '.$e->getMessage(),
            ];
        }
    }

    /**
     * Perform the actual restore operation.
     *
     * @return array<string, string>
     */
    private function performRestore(string $filePath): array
    {
        $preparation = $this->prepareBackupForRestore($filePath);
        if (! $preparation['success']) {
            return $preparation;
        }

        return $this->executeRestoreProcess($preparation['zip']);
    }

    /**
     * Prepare backup for restore by checking file existence and opening the zip archive.
     *
     * @return array<string, bool|string|\ZipArchive>
     */
    private function prepareBackupForRestore(string $filePath): array
    {
        if (! file_exists($filePath)) {
            return [
                'success' => false,
                'message' => 'Backup file not found',
            ];
        }

        $zip = new ZipArchive;
        if ($zip->open($filePath) !== true) {
            return [
                'success' => false,
                'message' => 'Cannot open backup file',
            ];
        }

        return [
            'success' => true,
            'zip' => $zip,
        ];
    }

    /**
     * Execute the restore process with prepared zip archive.
     *
     * @return array<string, string>
     */
    private function executeRestoreProcess(ZipArchive $zip): array
    {
        $tempDir = $this->extractBackup($zip);

        try {
            $this->restoreBackupContents($tempDir);

            return ['success' => true, 'message' => 'Backup restored successfully'];
        } finally {
            $this->removeDirectory($tempDir);
        }
    }

    /**
     * Extract backup to temporary directory.
     */
    private function extractBackup(ZipArchive $zip): string
    {
        $tempDir = storage_path('app/temp/restore_'.uniqid());
        mkdir($tempDir, 0755, true);

        $zip->extractTo($tempDir);
        $zip->close();

        return $tempDir;
    }

    /**
     * Restore backup contents (database and files).
     */
    private function restoreBackupContents(string $tempDir): void
    {
        $this->restoreDatabaseFromBackup($tempDir);
        $this->restoreFilesFromBackup($tempDir);
    }

    /**
     * Restore database from backup if present.
     */
    private function restoreDatabaseFromBackup(string $tempDir): void
    {
        $dbDumpFile = $tempDir.'/database_dump.sql';
        if (file_exists($dbDumpFile)) {
            $this->restoreDatabase($dbDumpFile);
        }
    }

    /**
     * Restore files from backup.
     */
    private function restoreFilesFromBackup(string $tempDir): void
    {
        try {
            $files = $this->getFilesToRestore($tempDir);
            $this->restoreFileCollection($files, $tempDir);
        } catch (\Exception $e) {
            Log::error('Error restoring files: '.$e->getMessage());
        }
    }

    /**
     * Restore database.
     */
    private function restoreDatabase(string $dumpFile): void
    {
        try {
            // Use Laravel's database restore command
            Artisan::call('db:restore', [
                '--path' => $dumpFile,
                '--force' => true,
            ]);
        } catch (\Exception $e) {
            Log::error('Error restoring database: '.$e->getMessage());
        }
    }

    /**
     * Get files to restore from temporary directory.
     */
    private function getFilesToRestore(string $tempDir): RecursiveIteratorIterator
    {
        return new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($tempDir),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
    }

    /**
     * Restore collection of files.
     */
    private function restoreFileCollection(RecursiveIteratorIterator $files, string $tempDir): void
    {
        foreach ($files as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile()) {
                $this->restoreSingleFile($file, $tempDir);
            }
        }
    }

    /**
     * Restore a single file.
     */
    private function restoreSingleFile(\SplFileInfo $file, string $tempDir): void
    {
        $realPath = $file->getRealPath();
        if ($realPath === false) {
            return;
        }

        $targetPath = $this->getTargetPath($realPath, $tempDir);
        $this->ensureTargetDirectory($targetPath);
        copy($realPath, $targetPath);
    }

    /**
     * Get target path for restored file.
     */
    private function getTargetPath(string $realPath, string $tempDir): string
    {
        $relativePath = substr($realPath, strlen($tempDir) + 1);

        return base_path($relativePath);
    }

    /**
     * Ensure target directory exists.
     */
    private function ensureTargetDirectory(string $targetPath): void
    {
        $targetDir = dirname($targetPath);
        if (! is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }
    }

    /**
     * Remove directory recursively.
     */
    private function removeDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir.'/'.$file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }
        rmdir($dir);
    }
}
