<?php

declare(strict_types=1);

namespace App\DTO;

class AnalysisResult
{
    public function __construct(
        public string $category,
        public int $score,
        public int $maxScore,
        /** @var array<string> */
        public array $issues
    ) {
    }
}
