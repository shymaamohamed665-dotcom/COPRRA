<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

use App\Services\Backup\Services\BackupConfigurationService;
use Exception;

final class ConfigurationBackupStrategy implements BackupStrategyInterface
{
    private BackupConfigurationService $configurationService;

    public function __construct(BackupConfigurationService $configurationService)
    {
        $this->configurationService = $configurationService;
    }

    /**
     * {@inheritdoc}
     */
    public function backup(string $backupDir, string $backupName): array
    {
        try {
            return $this->configurationService->backupConfiguration($backupDir);
        } catch (Exception $e) {
            throw new Exception("Configuration backup failed: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function restore(string $backupPath, array $backupInfo): array
    {
        try {
            return $this->configurationService->restoreConfiguration($backupPath, $backupInfo);
        } catch (Exception $e) {
            throw new Exception("Configuration restoration failed: {$e->getMessage()}", 0, $e);
        }
    }

    public function getComponentName(): string
    {
        return 'config';
    }

    /**
     * {@inheritdoc}
     */
    public function canHandle(array $backupInfo): bool
    {
        return isset($backupInfo['files']) &&
               isset($backupInfo['size']) &&
               isset($backupInfo['status']);
    }
}
