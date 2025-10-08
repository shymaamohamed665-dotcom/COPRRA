<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\Log;

class BackupFileService
{
    private string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = $backupPath;
    }

    /**
     * Delete backup file and log the operation.
     *
     * @param  array<string, string|int|null>  $backup
     */
    public function deleteBackupFile(array $backup, string $backupId): void
    {
        $filePath = $this->getBackupFilePath($backup);

        if (file_exists($filePath)) {
            unlink($filePath);
        }

        Log::info('Backup deleted: '.$backupId);
    }

    /**
     * Get backup file path.
     *
     * @param  array<string, string|int|null>  $backup
     */
    public function getBackupFilePath(array $backup): string
    {
        $filename = is_string($backup['filename'] ?? '') ? (string) ($backup['filename'] ?? '') : '';

        return $this->backupPath.'/'.$filename;
    }

    /**
     * Check if backup file exists.
     *
     * @param  array<string, string|int|null>  $backup
     */
    public function backupFileExists(array $backup): bool
    {
        $filePath = $this->getBackupFilePath($backup);

        return file_exists($filePath);
    }

    /**
     * Get backup file size.
     *
     * @param  array<string, string|int|null>  $backup
     */
    public function getBackupFileSize(array $backup): int
    {
        $filePath = $this->getBackupFilePath($backup);

        if (! file_exists($filePath)) {
            return 0;
        }

        $fileSize = filesize($filePath);

        return $fileSize !== false ? $fileSize : 0;
    }

    /**
     * Remove directory recursively.
     */
    public function removeDirectory(string $dir): void
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
