<?php

declare(strict_types=1);

namespace App\Services\Backup\Strategies;

interface BackupStrategyInterface
{
    /**
     * Perform the backup operation.
     *
     * @param  string  $backupDir  The directory where backup should be stored
     * @param  string  $backupName  The name of the backup
     *
     * @return array<string, mixed>
     */
    public function backup(string $backupDir, string $backupName): array;

    /**
     * Restore from backup.
     *
     * @param  string  $backupPath  The path to the backup directory
     * @param  array<string, mixed>  $backupInfo  Information about the backup component
     *
     * @return array<string, mixed>
     */
    public function restore(string $backupPath, array $backupInfo): array;

    /**
     * Get the component name this strategy handles.
     */
    public function getComponentName(): string;

    /**
     * Validate if this strategy can handle the given backup info.
     *
     * @param  array<string, mixed>  $backupInfo
     */
    public function canHandle(array $backupInfo): bool;
}
