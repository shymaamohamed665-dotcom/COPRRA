<?php

declare(strict_types=1);

namespace App\Services\FileCleanup;

use App\Services\FileCleanup\Strategies\BackupFilesCleanupStrategy;
use App\Services\FileCleanup\Strategies\CacheFilesCleanupStrategy;
use App\Services\FileCleanup\Strategies\LogFilesCleanupStrategy;
use App\Services\FileCleanup\Strategies\TempFilesCleanupStrategy;
use App\Services\FileCleanup\Strategies\UploadedFilesCleanupStrategy;

final class CleanupStrategyFactory
{
    /**
     * @param  array<string>  $config
     */
    public static function create(string $type, DirectoryCleaner $cleaner, array $config): TempFilesCleanupStrategy|LogFilesCleanupStrategy|CacheFilesCleanupStrategy|BackupFilesCleanupStrategy|UploadedFilesCleanupStrategy|null
    {
        switch ($type) {
            case 'temp':
                return new TempFilesCleanupStrategy($cleaner, [
                    storage_path('app/temp'),
                    storage_path('app/tmp'),
                    storage_path('framework/cache'),
                    storage_path('framework/sessions'),
                    storage_path('framework/views'),
                ], (int) ($config['temp_files_retention_days'] ?? 7));
            case 'logs':
                return new LogFilesCleanupStrategy($cleaner, storage_path('logs'), (int) ($config['log_files_retention_days'] ?? 30));
            case 'cache':
                return new CacheFilesCleanupStrategy($cleaner, [
                    storage_path('framework/cache'),
                    storage_path('framework/views'),
                    storage_path('framework/sessions'),
                ], (int) ($config['cache_files_retention_days'] ?? 14));
            case 'backups':
                return new BackupFilesCleanupStrategy($cleaner, storage_path('backups'), (int) ($config['backup_files_retention_days'] ?? 90));
            case 'uploads':
                return new UploadedFilesCleanupStrategy($cleaner, [
                    storage_path('app/public/uploads'),
                    public_path('uploads'),
                ], 30);
            default:
                return null;
        }
    }
}
