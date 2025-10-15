<?php

declare(strict_types=1);

namespace App\Services\FileCleanup\Strategies;

use App\Services\FileCleanup\DirectoryCleaner;
use Exception;
use Illuminate\Support\Facades\Log;

final class CacheFilesCleanupStrategy implements CleanupStrategy
{
    private DirectoryCleaner $cleaner;

    /**
     * @var array<string>
     */
    private array $directories;

    private int $retentionDays;

    /**
     * @param  array<string>  $directories
     */
    public function __construct(DirectoryCleaner $cleaner, array $directories, int $retentionDays)
    {
        $this->cleaner = $cleaner;
        $this->directories = $directories;
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
        $totalResults = [
            'cache_files' => 0,
            'deleted_size' => 0,
            'errors' => [],
        ];

        foreach ($this->directories as $directory) {
            $result = $this->cleaner->cleanup($directory, $this->retentionDays);
            $totalResults['cache_files'] += $result['files_deleted'];
            $totalResults['deleted_size'] += $result['size_deleted'];
        }

        try {
            \Artisan::call('cache:clear');
            \Artisan::call('view:clear');
            \Artisan::call('config:clear');
        } catch (Exception $e) {
            $totalResults['errors'][] = $e->getMessage();
            Log::error('Cache clearing failed', ['error' => $e->getMessage()]);
        }

        return $totalResults;
    }
}
