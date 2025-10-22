<?php

declare(strict_types=1);

namespace App\Services\FileCleanup\Strategies;

interface CleanupStrategy
{
    /**
     * @return array<string, int|string|array<string>>
     *
     * @psalm-return array<string, int|string|list<string>>
     */
    public function cleanup(): array;
}
