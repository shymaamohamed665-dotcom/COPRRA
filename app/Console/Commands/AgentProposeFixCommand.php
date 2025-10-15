<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\AgentFixer\AgentFixerFactory;
use App\Services\AgentFixer\FixExecutionService;
use App\Services\AgentFixer\GitWorkflowService;
use App\Services\AgentFixer\MessageGeneratorService;
use App\Services\AgentFixer\PullRequestService;
use App\Services\ProcessService;
use Illuminate\Console\Command;

/**
 * @property \Illuminate\Foundation\Application $laravel
 * @property \Symfony\Component\Console\Input\InputInterface $input
 * @property \Illuminate\Console\OutputStyle $output
 * @property \Illuminate\Console\View\Components\Factory $components
 * @property string|null $name
 *
 * @psalm-suppress PropertyNotSetInConstructor
 */
final class AgentProposeFixCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'agent:propose-fix {--type=style : The type of issue to fix (e.g., style, analysis)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Propose automated fixes via Pull Request for different types of issues';

    private GitWorkflowService $gitWorkflowService;

    private PullRequestService $pullRequestService;

    private MessageGeneratorService $messageGenerator;

    private FixExecutionService $fixExecutionService;

    private AgentFixerFactory $agentFixerFactory;

    /**
     * Create a new command instance.
     */
    public function __construct(
        private readonly ProcessService $processService
    ) {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @psalm-return 0|1
     */
    public function handle(): int
    {
        /** @var string $type */
        $type = $this->option('type');
        $this->info("🚀 Starting Agent Propose Fix process for type: {$type}");

        $this->initializeServices();

        $branchName = $this->generateBranchName($type);

        if (! $this->prepareAndCommitChanges($type, $branchName)) {
            return 1;
        }

        if (! $this->pushAndCreatePullRequest($type, $branchName)) {
            return 1;
        }

        $this->info('🎉 Agent Propose Fix process completed successfully!');
        $this->info("✅ Branch '{$branchName}' has been pushed and Pull Request created.");

        return 0;
    }

    private function initializeServices(): void
    {
        $this->gitWorkflowService = new GitWorkflowService($this->processService, $this->output);
        $this->pullRequestService = new PullRequestService($this->processService, $this->output);
        $this->messageGenerator = new MessageGeneratorService;
        $this->fixExecutionService = new FixExecutionService($this->processService, $this->output);
        $this->agentFixerFactory = new AgentFixerFactory($this->processService, $this->output);
    }

    /**
     * Generate a unique branch name.
     */
    private function generateBranchName(string $type): string
    {
        $timestamp = now()->format('Y-m-d-H-i-s');
        $branchName = "fix/{$type}-fixes-{$timestamp}";
        $this->info("📝 Generated branch name: {$branchName}");

        return $branchName;
    }

    /**
     * Prepare and commit changes to a new branch.
     */
    private function prepareAndCommitChanges(string $type, string $branchName): bool
    {
        if (! $this->gitWorkflowService->createBranch($branchName)) {
            return false;
        }

        if (! $this->fixExecutionService->executeFixerProcess($type, $this->agentFixerFactory)) {
            return false;
        }

        if (! $this->gitWorkflowService->stageChanges()) {
            $this->error('❌ Automated fix process failed during git workflow.');

            return false;
        }

        $commitMessage = $this->messageGenerator->getCommitMessage($type);
        $this->gitWorkflowService->commitChanges($commitMessage);

        return true;
    }

    /**
     * Push changes and create a pull request.
     */
    private function pushAndCreatePullRequest(string $type, string $branchName): bool
    {
        if (! $this->gitWorkflowService->pushBranch($branchName)) {
            return false;
        }

        $prTitle = $this->messageGenerator->getPullRequestTitle($type);
        $prBody = $this->messageGenerator->getPullRequestBody($type);

        return $this->pullRequestService->createPullRequest($branchName, $prTitle, $prBody);
    }
}
