<?php

declare(strict_types=1);

namespace App\DTO;

class AnalysisResult
{
    public function __construct(
        public string $category,
        public int $score,
        public int $max_score,
        /** @var array<string> */
        public array $issues
    ) {}
}
