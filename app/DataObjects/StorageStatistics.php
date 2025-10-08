<?php

declare(strict_types=1);

namespace App\DataObjects;

final class StorageStatistics
{
    /**
     * @param array<string, mixed> $usage
     * @param array<string, StorageBreakdown> $breakdown
     * @param list<array<string, string>> $recommendations
     */
    public function __construct(
        public readonly float $usage,
        public readonly array $breakdown,
        public readonly array $recommendations,
        public readonly int $total_files,
        public readonly ?string $oldest_file,
        public readonly ?string $newest_file
    ) {
    }
}
