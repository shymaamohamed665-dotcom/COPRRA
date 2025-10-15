<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

use App\Services\ProcessService;
use Illuminate\Console\OutputStyle;

final class AgentFixerFactory
{
    public function __construct(
        private readonly ProcessService $processService,
        private readonly OutputStyle $output
    ) {}

    public function create(string $type): StyleFixer|AnalysisFixer
    {
        return match ($type) {
            'style' => new StyleFixer($this->processService, $this->output),
            'analysis' => new AnalysisFixer($this->processService, $this->output),
            default => throw new \InvalidArgumentException("Unsupported fix type: {$type}"),
        };
    }
}
