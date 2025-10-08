<?php

declare(strict_types=1);

namespace App\DataObjects\Ai;

final class StageResult
{
    /**
     * @param  list<string>  $errors
     */
    public function __construct(
        public readonly bool $success,
        public readonly string $output,
        public readonly array $errors,
        public readonly float $duration,
        public readonly string $timestamp
    ) {}
}
