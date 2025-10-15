<?php

declare(strict_types=1);

namespace App\DataObjects;

final class StorageUsage
{
    public function __construct(
        public readonly float $currentSizeMb,
        public readonly float $maxSizeMb,
        public readonly float $usagePercentage,
        public readonly string $status,
        public readonly bool $needsCleanup,
        /** @var array<string, array{size_mb: float, size_bytes: int, path: string}> */
        public readonly array $breakdown
    ) {}

    /**
     * @return ((float|int|string)[][]|bool|float|string)[]
     *
     * @psalm-return array{current_size_mb: float, max_size_mb: float, usage_percentage: float, status: string, needs_cleanup: bool, breakdown: array<string, array{size_mb: float, size_bytes: int, path: string}>}
     */
    public function toArray(): array
    {
        return [
            'current_size_mb' => $this->currentSizeMb,
            'max_size_mb' => $this->maxSizeMb,
            'usage_percentage' => $this->usagePercentage,
            'status' => $this->status,
            'needs_cleanup' => $this->needsCleanup,
            'breakdown' => $this->breakdown,
        ];
    }
}
