<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

use Illuminate\Console\OutputStyle;

final readonly class FixExecutionService
{
    public function __construct(
        private OutputStyle $output
    ) {
    }

    public function executeFixerProcess(string $type, AgentFixerFactory $agentFixerFactory): bool
    {
        try {
            $fixer = $agentFixerFactory->create($type);
            if (! $fixer->fix()) {
                $this->output->error('❌ Automated fix process failed.');

                return false;
            }
        } catch (\InvalidArgumentException $e) {
            $this->output->error("❌ {$e->getMessage()}");
            $this->output->info('Supported types: style, analysis');

            return false;
        }

        return true;
    }
}
