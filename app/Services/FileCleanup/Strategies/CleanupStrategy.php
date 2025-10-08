<?php

declare(strict_types=1);

namespace App\Services\FileCleanup\Strategies;

interface CleanupStrategy
{
    /**
     * @return array<string, bool|string|int|null>
     */
    public function cleanup(): array;
}
