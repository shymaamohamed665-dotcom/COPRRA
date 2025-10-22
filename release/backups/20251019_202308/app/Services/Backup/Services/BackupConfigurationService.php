<?php

declare(strict_types=1);

namespace App\Services\Backup\Services;

use Exception;

final class BackupConfigurationService
{
    /**
     * Backup configuration.
     *
     * @return array{
     *     files: list<string>,
     *     size: int,
     *     status: string
     * }
     *
     * @throws Exception
     */
    public function backupConfiguration(string $backupDir): array
    {
        $configDir = $backupDir.'/config';
        mkdir($configDir, 0o755, true);

        $configFiles = [
            '.env' => base_path('.env'),
            'app.php' => config_path('app.php'),
            'database.php' => config_path('database.php'),
            'cache.php' => config_path('cache.php'),
        ];

        $backedUpFiles = [];

        foreach ($configFiles as $name => $sourcePath) {
            $this->processSingleConfigFileBackup($name, $sourcePath, $configDir, $backedUpFiles);
        }

        return [
            'files' => $backedUpFiles,
            'size' => $this->getDirectorySize($configDir),
            'status' => 'completed',
        ];
    }

    /**
     * Restore configuration.
     *
     * @param  array{files?: list<string>, size?: int, status?: string}  $configInfo
     *
     * @return array{files: list<string>, status: string}
     *
     * @throws Exception
     */
    public function restoreConfiguration(string $backupPath, array $configInfo): array
    {
        $configDir = $backupPath.'/config';

        if (! is_dir($configDir)) {
            throw new Exception('Configuration backup directory not found');
        }

        $restoredFiles = [];

        $files = $configInfo['files'] ?? [];
        if (is_array($files)) {
            foreach ($files as $file) {
                $this->processSingleConfigRestore($configDir, $file, $restoredFiles);
            }
        }

        return [
            'files' => $restoredFiles,
            'status' => 'completed',
        ];
    }

    /**
     * Process single config file backup.
     */
    private function processSingleConfigFileBackup(string $name, string $sourcePath, string $configDir, array &$backedUpFiles): void
    {
        if (file_exists($sourcePath)) {
            $destPath = $configDir.'/'.$name;
            copy($sourcePath, $destPath);
            $backedUpFiles[] = $name;
        }
    }

    /**
     * Process single config restore.
     */
    private function processSingleConfigRestore(string $configDir, string $file, array &$restoredFiles): void
    {
        if (is_string($file)) {
            $sourcePath = $configDir.'/'.$file;
            $destPath = $this->getConfigDestinationPath($file);

            if (file_exists($sourcePath)) {
                copy($sourcePath, $destPath);
                $restoredFiles[] = $file;
            }
        }
    }

    /**
     * Get configuration destination path.
     */
    private function getConfigDestinationPath(string $file): string
    {
        $destinations = [
            '.env' => base_path('.env'),
            'app.php' => config_path('app.php'),
            'database.php' => config_path('database.php'),
            'cache.php' => config_path('cache.php'),
        ];

        return $destinations[$file] ?? config_path($file);
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

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
