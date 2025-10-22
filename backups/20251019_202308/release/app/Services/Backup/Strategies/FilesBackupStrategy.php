<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

use App\Services\Backup\Services\BackupFileSystemService;
use Exception;

final readonly class FilesBackupStrategy implements BackupStrategyInterface
{
    private BackupFileSystemService $fileSystemService;

    public function __construct(BackupFileSystemService $fileSystemService)
    {
        $this->fileSystemService = $fileSystemService;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function backup(string $backupDir, string $backupName): array
    {
        try {
            return $this->fileSystemService->backupFiles($backupDir);
        } catch (Exception $e) {
            throw new Exception("Files backup failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function restore(string $backupPath, array $backupInfo): array
    {
        try {
            return $this->fileSystemService->restoreFiles($backupPath, $backupInfo);
        } catch (Exception $e) {
            throw new Exception("Files restoration failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @psalm-return 'files'
     */
    #[\Override]
    public function getComponentName(): string
    {
        return 'files';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function canHandle(array $backupInfo): bool
    {
        return isset($backupInfo['directories']) &&
               isset($backupInfo['size']) &&
               isset($backupInfo['status']);
    }
}
