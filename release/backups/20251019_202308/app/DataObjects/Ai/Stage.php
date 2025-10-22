<?php

declare(strict_types=1);

namespace App\DataObjects\Ai;

use App\Enums\Ai\AgentStage;

final readonly class Stage
{
    public function __construct(
        public AgentStage $name,
        public string $command,
        public bool $strict,
        public bool $required,
        public ?array $files = null
    ) {
    }
}
