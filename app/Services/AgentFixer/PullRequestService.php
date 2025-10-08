<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

use App\Services\ProcessService;
use Illuminate\Console\OutputStyle;

final class PullRequestService
{
    public function __construct(
        private readonly ProcessService $processService,
        private readonly OutputStyle $output
    ) {}

    public function createPullRequest(string $branchName, string $prTitle, string $prBody): bool
    {
        $this->output->info('🔗 Creating Pull Request...');
        $prResult = $this->processService->run([
            'gh',
            'pr',
            'create',
            '--base',
            'main',
            '--head',
            $branchName,
            '--title',
            $prTitle,
            '--body',
            $prBody,
        ]);

        if ($prResult->failed()) {
            $this->output->error('❌ Failed to create Pull Request: '.$prResult->getErrorOutput());
            $this->output->warn('⚠️ Branch was pushed successfully, but PR creation failed.');
            $this->output->warn('You can manually create the PR at: https://github.com/your-repo/compare/main...'.$branchName);

            return false;
        }

        $this->output->info('✅ Pull Request created successfully');
        $this->output->info('PR output: '.$prResult->getOutput());

        return true;
    }
}
