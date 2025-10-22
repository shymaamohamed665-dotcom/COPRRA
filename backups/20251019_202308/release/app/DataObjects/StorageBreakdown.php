<?php

declare(strict_types=1);

namespace App\DataObjects;

final readonly class StorageBreakdown
{
    public function __construct(
        public float $sizeMb,
        public int $sizeBytes,
        public string $path,
    ) {}
}
