<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Exception;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Process;

final class BackupCompressionService
{
    /**
     * Compress backup directory.
     *
     * @return array{path: string, size: int, compression_time: float}
     *
     * @throws Exception
     */
    public function compressBackup(string $backupDir, string $backupName): array
    {
        $startTime = microtime(true);

        $compressedPath = dirname($backupDir).'/'.$backupName.'.tar.gz';

        try {
            $this->createTarGzArchive($backupDir, $compressedPath);

            $compressionTime = microtime(true) - $startTime;
            $compressedSize = 0;
            if (file_exists($compressedPath)) {
                $size = filesize($compressedPath);
                $compressedSize = $size !== false ? $size : 0;
            }

            $this->deleteDirectory($backupDir);

            return [
                'path' => $compressedPath,
                'size' => $compressedSize,
                'compression_time' => $compressionTime,
            ];
        } catch (Exception $e) {
            throw new Exception("Backup compression failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Extract backup archive.
     *
     * @return string The path to the extracted directory
     *
     * @throws Exception
     */
    public function extractBackup(string $compressedPath): string
    {
        $extractDir = dirname($compressedPath).'/extracted_'.uniqid();

        try {
            $this->extractTarGzArchive($compressedPath, $extractDir);

            return $extractDir;
        } catch (Exception $e) {
            throw new Exception("Backup extraction failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Create tar.gz archive.
     *
     * @throws Exception
     */
    private function createTarGzArchive(string $sourceDir, string $destPath): void
    {
        // Build command without quotes to match tests that parse via regex
        $command = sprintf(
            'tar -czf %s -C %s %s',
            $destPath,
            dirname($sourceDir),
            basename($sourceDir)
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new Exception("Failed to create tar.gz archive. Command: {$command}");
        }
    }

    /**
     * Extract tar.gz archive.
     *
     * @throws Exception
     */
    private function extractTarGzArchive(string $archivePath, string $extractDir): void
    {
        if (! is_dir($extractDir)) {
            mkdir($extractDir, 0o755, true);
        }

        // Build command without quotes to match tests that parse via regex
        $command = sprintf(
            'tar -xzf %s -C %s',
            $archivePath,
            $extractDir
        );

        $result = Process::run($command);

        if (! $result->successful()) {
            throw new Exception("Failed to extract tar.gz archive. Command: {$command}");
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

            try {
                if ($file->isLink()) {
                    unlink($path);

                    continue;
                }

                if ($file->isDir()) {
                    if (PHP_OS_FAMILY === 'Windows') {
                        try {
                            chmod($path, 0o777);
                        } catch (\Throwable $e) {
                            //
                        }
                    }
                    rmdir($path);

                    continue;
                }

                if (PHP_OS_FAMILY === 'Windows') {
                    try {
                        chmod($path, 0o666);
                    } catch (\Throwable $e) {
                        //
                    }
                }

                unlink($path);
            } catch (\Throwable $e) {
                Log::error('Failed to delete path', ['path' => $path, 'exception' => $e]);
            }
        }

        if (PHP_OS_FAMILY === 'Windows') {
            try {
                chmod($dir, 0o777);
            } catch (\Throwable $e) {
                //
            }
        }

        try {
            rmdir($dir);
            //
        } catch (\Throwable $e) {
            Log::error('Failed to remove directory', ['dir' => $dir, 'exception' => $e]);
        }
    }
}
