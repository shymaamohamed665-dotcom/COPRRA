<?php

declare(strict_types=1);

namespace App\DataObjects\Ai;

final readonly class StageResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public bool $success,
        public string $output,
        public array $errors,
        public float $duration,
        public string $timestamp
    ) {
    }
}
