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
     * @return array<string, float|string|bool|array<string, array{size_mb: float, size_bytes: int, path: string}>>
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
