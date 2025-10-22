<?php

declare(strict_types=1);

namespace App\Contracts;

interface FileSecurityService
{
    /**
     * Get file security statistics.
     *
     * @return array<string, int|float|array<int, string>>
     */
    public function getStatistics(): array;
}
