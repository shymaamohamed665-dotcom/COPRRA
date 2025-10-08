<?php

declare(strict_types=1);

namespace App\DataObjects\Ai;

use App\Enums\Ai\AgentStage;

final class Stage
{
    public function __construct(
        public readonly AgentStage $name,
        public readonly string $command,
        public readonly bool $strict,
        public readonly bool $required,
        public readonly ?array $files = null
    ) {}
}
