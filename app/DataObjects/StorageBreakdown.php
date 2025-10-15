<?php

declare(strict_types=1);

namespace App\DataObjects;

final class StorageBreakdown
{
    public function __construct(
        public readonly float $sizeMb,
        public readonly int $sizeBytes,
        public readonly string $path,
    ) {}
}
