<?php

declare(strict_types=1);

namespace App\DataObjects;

final readonly class StorageStatistics
{
    /**
     * @param  array{current_size_mb: float, max_size_mb: float, usage_percentage: float, status: string, needs_cleanup: bool, breakdown: array<string, array{size_mb: float, size_bytes: int, path: string}>}  $usage
     * @param  array<string, StorageBreakdown>  $breakdown
     * @param  list<array<string, string>>  $recommendations
     * @param  array{total_files: int, oldest_file: ?string, newest_file: ?string}  $file_stats
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public array $usage,
        public array $breakdown,
        public array $recommendations,
        public array $fileStats,
        public array $config,
    ) {
    }
}
