<?php

declare(strict_types=1);

namespace App\Services\FileCleanup\Strategies;

use App\Services\FileCleanup\DirectoryCleaner;

final class BackupFilesCleanupStrategy implements CleanupStrategy
{
    private DirectoryCleaner $cleaner;

    private string $directory;

    private int $retentionDays;

    public function __construct(DirectoryCleaner $cleaner, string $directory, int $retentionDays)
    {
        $this->cleaner = $cleaner;
        $this->directory = $directory;
        $this->retentionDays = $retentionDays;
    }

    /**
     * @return array<string, int|string|string[]>
     *
     * @psalm-return array<string, int|string|list<string>>
     */
    #[\Override]
    public function cleanup(): array
    {
        $result = $this->cleaner->cleanup($this->directory, $this->retentionDays);

        return [
            'backup_files' => $result['files_deleted'] ?? 0,
            'deleted_size' => $result['size_deleted'] ?? 0,
            'errors' => [],
        ];
    }
}
