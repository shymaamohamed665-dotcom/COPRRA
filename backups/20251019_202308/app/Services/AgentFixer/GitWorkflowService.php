<?php

declare(strict_types=1);

namespace App\Services\AgentFixer;

use App\Services\ProcessService;
use Illuminate\Console\OutputStyle;

final readonly class GitWorkflowService
{
    public function __construct(
        private ProcessService $processService,
        private OutputStyle $output
    ) {}

    public function createBranch(string $branchName): bool
    {
        $this->output->info('🌿 Creating and switching to new branch...');
        $checkoutResult = $this->processService->run("git checkout -b {$branchName}");

        if ($checkoutResult->failed()) {
            $this->output->error('❌ Failed to create branch: '.$checkoutResult->getErrorOutput());

            return false;
        }

        $this->output->info('✅ Branch created successfully');
        $this->output->info('Git output: '.$checkoutResult->getOutput());

        return true;
    }

    public function stageChanges(): bool
    {
        $this->output->info('📦 Staging all changes...');
        $addResult = $this->processService->run('git add .');

        if ($addResult->failed()) {
            $this->output->error('❌ Failed to stage changes: '.$addResult->getErrorOutput());

            return false;
        }

        $this->output->info('✅ Changes staged successfully');
        $this->output->info('Git add output: '.$addResult->getOutput());

        return true;
    }

    public function commitChanges(string $commitMessage): bool
    {
        $this->output->info('💾 Committing changes...');
        $commitResult = $this->processService->run("git commit -m \"{$commitMessage}\"");

        if ($commitResult->failed()) {
            $this->output->warn('⚠️ No changes to commit or commit failed: '.$commitResult->getErrorOutput());
            $this->output->info('Git commit output: '.$commitResult->getOutput());

            return false;
        }

        $this->output->info('✅ Changes committed successfully');
        $this->output->info('Git commit output: '.$commitResult->getOutput());

        return true;
    }

    public function pushBranch(string $branchName): bool
    {
        $this->output->info('🚀 Pushing branch to remote repository...');
        $pushResult = $this->processService->run("git push --set-upstream origin {$branchName}");

        if ($pushResult->failed()) {
            $this->output->error('❌ Failed to push branch: '.$pushResult->getErrorOutput());

            return false;
        }

        $this->output->info('✅ Branch pushed successfully');
        $this->output->info('Git push output: '.$pushResult->getOutput());

        return true;
    }
}
