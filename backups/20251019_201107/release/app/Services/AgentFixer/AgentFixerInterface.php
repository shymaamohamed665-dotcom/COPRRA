<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

interface AgentFixerInterface
{
    public function fix(): bool;
}
