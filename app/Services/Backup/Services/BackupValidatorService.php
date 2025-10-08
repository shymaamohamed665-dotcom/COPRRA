<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Exception;

final class BackupValidatorService
{
    /**
     * Validate backup parameters.
     *
     * @throws Exception
     */
    public function validateBackupParameters(string $type, array $options): void
    {
        $this->validateBackupType($type);
        $this->validateBackupOptions($type, $options);
    }

    /**
     * Validate restore parameters.
     *
     * @throws Exception
     */
    public function validateRestoreParameters(string $backupPath, array $options): void
    {
        $this->validateBackupPath($backupPath);
        $this->validateRestoreOptions($options);
    }

    /**
     * Validate backup directory.
     *
     * @throws Exception
     */
    public function validateBackupDirectory(string $backupDir): void
    {
        if (! is_dir($backupDir)) {
            throw new Exception("Backup directory does not exist: {$backupDir}");
        }

        if (! is_writable($backupDir)) {
            throw new Exception("Backup directory is not writable: {$backupDir}");
        }
    }

    /**
     * Validate backup manifest.
     *
     * @param  array{type?: string, name?: string, created_at?: string, size?: int, components?: array}  $manifest
     *
     * @throws Exception
     */
    public function validateBackupManifest(array $manifest): void
    {
        if (! isset($manifest['type'])) {
            throw new Exception('Backup manifest missing type');
        }

        if (! isset($manifest['name'])) {
            throw new Exception('Backup manifest missing name');
        }

        if (! isset($manifest['created_at'])) {
            throw new Exception('Backup manifest missing created_at');
        }

        if (! isset($manifest['size'])) {
            throw new Exception('Backup manifest missing size');
        }

        if (! isset($manifest['components'])) {
            throw new Exception('Backup manifest missing components');
        }

        $this->validateBackupType($manifest['type']);
    }

    /**
     * Validate backup type.
     *
     * @throws Exception
     */
    private function validateBackupType(string $type): void
    {
        $validTypes = ['full', 'database', 'files'];

        if (! in_array($type, $validTypes, true)) {
            throw new Exception("Invalid backup type: {$type}. Valid types: ".implode(', ', $validTypes));
        }
    }

    /**
     * Validate backup options.
     *
     * @throws Exception
     */
    private function validateBackupOptions(string $type, array $options): void
    {
        if (isset($options['directories']) && ! is_array($options['directories'])) {
            throw new Exception('Backup directories must be an array');
        }

        if (isset($options['tables']) && ! is_array($options['tables'])) {
            throw new Exception('Backup tables must be an array');
        }

        if (isset($options['compress']) && ! is_bool($options['compress'])) {
            throw new Exception('Backup compress option must be a boolean');
        }
    }

    /**
     * Validate backup path.
     *
     * @throws Exception
     */
    private function validateBackupPath(string $backupPath): void
    {
        if (! file_exists($backupPath)) {
            throw new Exception("Backup file does not exist: {$backupPath}");
        }

        if (! is_readable($backupPath)) {
            throw new Exception("Backup file is not readable: {$backupPath}");
        }
    }

    /**
     * Validate restore options.
     *
     * @throws Exception
     */
    private function validateRestoreOptions(array $options): void
    {
        if (isset($options['components']) && ! is_array($options['components'])) {
            throw new Exception('Restore components must be an array');
        }

        if (isset($options['overwrite']) && ! is_bool($options['overwrite'])) {
            throw new Exception('Restore overwrite option must be a boolean');
        }
    }
}
