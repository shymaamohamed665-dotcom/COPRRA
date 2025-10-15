<?php

declare(strict_types=1);

namespace App\DataObjects;

final class StorageStatistics
{
    /**
     * @param  array{current_size_mb: float, max_size_mb: float, usage_percentage: float, status: string, needs_cleanup: bool, breakdown: array<string, array{size_mb: float, size_bytes: int, path: string}>}  $usage
     * @param  array<string, StorageBreakdown>  $breakdown
     * @param  list<array<string, string>>  $recommendations
     * @param  array{total_files: int, oldest_file: ?string, newest_file: ?string}  $file_stats
     * @param  array<string, mixed>  $config
     */
    public function __construct(
        public readonly array $usage,
        public readonly array $breakdown,
        public readonly array $recommendations,
        public readonly array $fileStats,
        public readonly array $config,
    ) {}
}
