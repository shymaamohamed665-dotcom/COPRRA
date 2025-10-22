<?php

declare(strict_types=1);

namespace App\Services\Backup;

use Illuminate\Support\Facades\Log;

class BackupListService
{
    private readonly string $backupPath;

    public function __construct(string $backupPath)
    {
        $this->backupPath = $backupPath;
    }

    /**
     * Get backups list.
     *
     * @return list<array<string, int|string>>
     */
    public function getBackupsList(): array
    {
        try {
            return $this->fetchAndProcessBackups();
        } catch (\Exception $e) {
            Log::error('Error getting backups list: '.$e->getMessage());

            return [];
        }
    }

    /**
     * Get backup by ID.
     *
     * @return array<int|string>|null
     *
     * @psalm-return array<string, int|string>|null
     */
    public function getBackupById(string $id): ?array
    {
        $backups = $this->getBackupsList();

        foreach ($backups as $backup) {
            if ($backup['id'] === $id) {
                return $backup;
            }
        }

        return null;
    }

    /**
     * Calculate backup statistics.
     *
     * @return array<int|string|null>
     *
     * @psalm-return array{total_backups: int<0, max>, total_size: int, total_size_formatted: string, oldest_backup: null, newest_backup: null}
     */
    public function calculateBackupStatistics(): array
    {
        $backups = $this->getBackupsList();
        $totalSize = $this->calculateTotalSize($backups);

        return [
            'total_backups' => count($backups),
            'total_size' => $totalSize,
            'total_size_formatted' => $this->formatBytes($totalSize),
            'oldest_backup' => null,
            'newest_backup' => null,
        ];
    }

    /**
     * Fetch and process all backups.
     *
     * @return list<array<string, int|string>>
     */
    private function fetchAndProcessBackups(): array
    {
        $this->ensureBackupDirectoryExists();
        $files = $this->getBackupFiles();

        return $this->processBackupFiles($files);
    }

    /**
     * Ensure backup directory exists.
     */
    private function ensureBackupDirectoryExists(): void
    {
        if (! is_dir($this->backupPath)) {
            mkdir($this->backupPath, 0o755, true);
        }
    }

    /**
     * Get list of files in backup directory.
     *
     * @return array<string>
     *
     * @psalm-return list<string>
     */
    private function getBackupFiles(): array
    {
        $files = scandir($this->backupPath);

        return $files === false ? [] : $files;
    }

    /**
     * Process backup files and create metadata.
     *
     * @param  array<int, string>  $files
     *
     * @return list<array<string, int|string>>
     */
    private function processBackupFiles(array $files): array
    {
        $backups = $this->extractValidBackups($files);

        return $this->sortBackupsByDate($backups);
    }

    /**
     * Extract valid backup files and create metadata.
     *
     * @param  array<int, string>  $files
     *
     * @return list<array<string, int|string>>
     */
    private function extractValidBackups(array $files): array
    {
        $backups = [];
        foreach ($files as $file) {
            $backupData = $this->processSingleBackupFile($file);
            if ($backupData !== null) {
                $backups[] = $backupData;
            }
        }

        return $backups;
    }

    /**
     * Process a single backup file and return metadata if valid.
     *
     * @return array<int|string>|null
     *
     * @psalm-return array{id: string, filename: string, size: int, size_formatted: string, created_at: string, type: string}|null
     */
    private function processSingleBackupFile(string $file): ?array
    {
        if (! $this->isValidBackupFile($file)) {
            return null;
        }

        return $this->createBackupMetadata($file);
    }

    /**
     * Check if file is a valid backup file.
     */
    private function isValidBackupFile(string $file): bool
    {
        return $file !== '.' && $file !== '..' && is_file($this->backupPath.'/'.$file);
    }

    /**
     * Create backup metadata for a file.
     *
     * @return array<int|string>|null
     *
     * @psalm-return array{id: string, filename: string, size: int, size_formatted: string, created_at: string, type: 'database'|'files'|'full'}|null
     */
    private function createBackupMetadata(string $file): ?array
    {
        $filePath = $this->backupPath.'/'.$file;
        $fileSize = filesize($filePath);
        $fileTime = filemtime($filePath);

        if ($fileSize === false || $fileTime === false) {
            return null;
        }

        return [
            'id' => pathinfo($file, PATHINFO_FILENAME),
            'filename' => $file,
            'size' => $fileSize,
            'size_formatted' => $this->formatBytes($fileSize),
            'created_at' => date('Y-m-d H:i:s', $fileTime),
            'type' => $this->getBackupType($file),
        ];
    }

    /**
     * Sort backups by creation date (newest first).
     *
     * @param  list<array<string, int|string>>  $backups
     *
     * @return list<array<string, int|string>>
     */
    private function sortBackupsByDate(array $backups): array
    {
        usort($backups, static fn (array $a, array $b): int => strtotime((string) $b['created_at']) - strtotime((string) $a['created_at']));

        return $backups;
    }

    /**
     * Calculate total size of all backups.
     *
     * @param  list<array<string, int|string>>  $backups
     *
     * @psalm-return int<min, max>
     */
    private function calculateTotalSize(array $backups): int
    {
        $totalSize = 0;

        foreach ($backups as $backup) {
            $filePath = $this->backupPath.'/'.$backup['filename'];
            if (file_exists($filePath)) {
                $totalSize += filesize($filePath);
            }
        }

        return $totalSize;
    }

    /**
     * Get backup type from filename.
     *
     * @psalm-return 'database'|'files'|'full'
     */
    private function getBackupType(string $filename): string
    {
        if (str_contains($filename, 'database')) {
            return 'database';
        }
        if (str_contains($filename, 'files')) {
            return 'files';
        }

        return 'full';
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
