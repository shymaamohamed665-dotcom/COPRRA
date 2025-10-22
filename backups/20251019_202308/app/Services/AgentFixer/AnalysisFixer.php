<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

use App\Services\ProcessService;
use Illuminate\Console\OutputStyle;

final readonly class AnalysisFixer implements AgentFixerInterface
{
    public function __construct(
        private ProcessService $processService,
        private OutputStyle $output
    ) {}

    #[\Override]
    public function fix(): bool
    {
        $this->output->info('🔍 Running PHPStan to generate a baseline...');

        $this->ensureBaselineFileExists();

        $phpstanPath = implode(DIRECTORY_SEPARATOR, ['.', 'vendor', 'bin', 'phpstan']);
        $phpstanResult = $this->processService->run(['php', '-d', 'memory_limit=512M', $phpstanPath, 'analyse', '--generate-baseline']);

        if ($phpstanResult->failed()) {
            $this->output->error('❌ PHPStan baseline generation failed: '.$phpstanResult->getErrorOutput());
            $this->output->error('PHPStan output: '.$phpstanResult->getOutput());

            return false;
        }

        $this->output->info('✅ PHPStan baseline generated successfully');
        $this->output->info('PHPStan output: '.$phpstanResult->getOutput());

        return true;
    }

    private function ensureBaselineFileExists(): void
    {
        $baselinePath = 'phpstan-baseline.neon';

        if (! file_exists($baselinePath)) {
            $this->output->info('INFO: Baseline file not found. Creating an empty one.');
            file_put_contents($baselinePath, '');
        }
    }
}
