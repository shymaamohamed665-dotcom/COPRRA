<?php

declare(strict_types=1);

namespace App\Services\FileCleanup\Strategies;

use App\Services\FileCleanup\DirectoryCleaner;

final class LogFilesCleanupStrategy implements CleanupStrategy
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
     * @return array<string, int>
     */
    public function cleanup(): array
    {
        return $this->cleaner->cleanup($this->directory, $this->retentionDays, '*.log');
    }
}
