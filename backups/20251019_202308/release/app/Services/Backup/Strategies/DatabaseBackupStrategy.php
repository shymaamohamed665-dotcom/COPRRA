<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

use App\Services\Backup\Services\BackupDatabaseService;
use Exception;

final readonly class DatabaseBackupStrategy implements BackupStrategyInterface
{
    private BackupDatabaseService $databaseService;

    public function __construct(BackupDatabaseService $databaseService)
    {
        $this->databaseService = $databaseService;
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function backup(string $backupDir, string $backupName): array
    {
        try {
            return $this->databaseService->backupDatabase($backupDir);
        } catch (Exception $e) {
            throw new Exception("Database backup failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function restore(string $backupPath, array $backupInfo): array
    {
        try {
            return $this->databaseService->restoreDatabase($backupPath, $backupInfo);
        } catch (Exception $e) {
            throw new Exception("Database restoration failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * @psalm-return 'database'
     */
    #[\Override]
    public function getComponentName(): string
    {
        return 'database';
    }

    /**
     * {@inheritdoc}
     */
    #[\Override]
    public function canHandle(array $backupInfo): bool
    {
        return isset($backupInfo['filename']) &&
               isset($backupInfo['size']) &&
               isset($backupInfo['status']);
    }
}
