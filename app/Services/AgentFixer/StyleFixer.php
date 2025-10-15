<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

use App\Services\ProcessService;
use Illuminate\Console\OutputStyle;

final class StyleFixer implements AgentFixerInterface
{
    public function __construct(
        private readonly ProcessService $processService,
        private readonly OutputStyle $output
    ) {}

    /**
     * @return true
     */
    #[\Override]
    public function fix(): bool
    {
        $this->output->info('🎨 Running Laravel Pint code style fixer...');
        $pintPath = implode(DIRECTORY_SEPARATOR, ['.', 'vendor', 'bin', 'pint']);
        $pintResult = $this->processService->run($pintPath);

        if ($pintResult->failed()) {
            $this->output->warn('⚠️ Pint encountered issues: '.$pintResult->getErrorOutput());
            $this->output->info('Pint output: '.$pintResult->getOutput());

            return true;
        }

        $this->output->info('✅ Pint completed successfully');
        $this->output->info('Pint output: '.$pintResult->getOutput());

        return true;
    }
}
