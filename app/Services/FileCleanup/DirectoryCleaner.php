<?php

declare(strict_types=1);

namespace App\Services\FileCleanup;

use Carbon\Carbon;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

final class DirectoryCleaner
{
    /**
     * @return array<string, int>
     */
    public function cleanup(string $directory, int $retentionDays): array
    {
        $filesDeleted = 0;
        $sizeDeleted = 0;
        $cutoffDate = Carbon::now()->subDays($retentionDays);

        if (! is_dir($directory)) {
            return ['files_deleted' => 0, 'size_deleted' => 0];
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($iterator as $file) {
            if ($file instanceof \SplFileInfo && $file->isFile() && $file->getMTime() < $cutoffDate->timestamp) {
                $size = $file->getSize();
                if (unlink($file->getPathname())) {
                    $filesDeleted++;
                    $sizeDeleted += $size;
                }
            }
        }

        return [
            'files_deleted' => $filesDeleted,
            'size_deleted' => $sizeDeleted,
        ];
    }
}
