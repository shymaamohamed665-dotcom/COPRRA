<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class BackupFileSystemService
{
    /**
     * Backup files.
     *
     * @return array{
     *     directories: list<string>,
     *     size: int,
     *     status: string
     * }
     *
     * @throws Exception
     */
    public function backupFiles(string $backupDir): array
    {
        $filesDir = $this->joinPaths($backupDir, 'files');
        mkdir($filesDir, 0o755, true);

        $sourceDirs = [
            'storage/app' => storage_path('app'),
            'storage/logs' => storage_path('logs'),
            'public/uploads' => public_path('uploads'),
        ];

        $backedUpDirs = [];

        foreach ($sourceDirs as $name => $sourcePath) {
            $this->processSingleFileBackup($name, $sourcePath, $filesDir, $backedUpDirs);
        }

        return [
            'directories' => $backedUpDirs,
            'size' => $this->getDirectorySize($filesDir),
            'status' => 'completed',
        ];
    }

    /**
     * Restore files.
     *
     * @param  array{directories?: list<string>, size?: int, status?: string}  $filesInfo
     * @return array{directories: list<string>, status: string}
     *
     * @throws Exception
     */
    public function restoreFiles(string $backupPath, array $filesInfo): array
    {
        $filesDir = $backupPath.'/files';

        if (! is_dir($filesDir)) {
            throw new Exception('Files backup directory not found');
        }

        $restoredDirs = [];

        $directories = $filesInfo['directories'] ?? [];
        if (is_array($directories)) {
            foreach ($directories as $dir) {
                $this->processSingleFileRestore($filesDir, $dir, $restoredDirs);
            }
        }

        return [
            'directories' => $restoredDirs,
            'status' => 'completed',
        ];
    }

    /**
     * Process single file backup.
     */
    private function processSingleFileBackup(string $name, string $sourcePath, string $filesDir, array &$backedUpDirs): void
    {
        if (is_dir($sourcePath)) {
            $destPath = $this->joinPaths($filesDir, $name);
            $this->copyDirectory($sourcePath, $destPath);
            $backedUpDirs[] = $name;
        }
    }

    /**
     * Process single file restore.
     */
    private function processSingleFileRestore(string $filesDir, string $dir, array &$restoredDirs): void
    {
        if (is_string($dir)) {
            $sourcePath = $this->joinPaths($filesDir, $dir);
            $destPath = $this->getDestinationPath($dir);

            if (is_dir($sourcePath)) {
                $this->copyDirectory($sourcePath, $destPath);
                $restoredDirs[] = $dir;
            }
        }
    }

    /**
     * Normalize and join path parts using platform-specific separator.
     */
    private function joinPaths(string ...$parts): string
    {
        $trimmed = array_map(static fn (string $p): string => trim($p, '\\/'), $parts);

        return implode(DIRECTORY_SEPARATOR, $trimmed);
    }

    /**
     * Copy directory recursively.
     */
    private function copyDirectory(string $source, string $dest): void
    {
        if (! is_dir($dest)) {
            mkdir($dest, 0o755, true);
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $this->processSingleItemCopy($item, $dest, $iterator);
        }
    }

    /**
     * Process single item copy.
     */
    private function processSingleItemCopy(\SplFileInfo $item, string $dest, RecursiveIteratorIterator $iterator): void
    {
        $destPath = $dest.DIRECTORY_SEPARATOR.$iterator->getSubPathName();
        if ($item->isDir()) {
            mkdir($destPath, 0o755, true);
        } else {
            copy($item->getPathname(), $destPath);
        }
    }

    /**
     * Get destination path for file restoration.
     */
    private function getDestinationPath(string $dir): string
    {
        $destinations = [
            'storage/app' => storage_path('app'),
            'storage/logs' => storage_path('logs'),
            'public/uploads' => public_path('uploads'),
        ];

        return $destinations[$dir] ?? storage_path('app');
    }

    /**
     * Get directory size.
     *
     * @psalm-return int<min, max>
     */
    private function getDirectorySize(string $dir): int
    {
        $size = 0;

        if (! is_dir($dir)) {
            return $size;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
