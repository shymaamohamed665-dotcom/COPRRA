<?php

declare(strict_types=1);

namespace App\DataObjects;

final class StorageBreakdown
{
    public function __construct(
        public readonly float $size_mb,
        public readonly int $size_bytes,
        public readonly string $path
    ) {}
}
