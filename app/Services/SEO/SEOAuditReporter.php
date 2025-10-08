<?php

declare(strict_types=1);

namespace App\Services\SEO;

use Illuminate\Console\Command;

final class SEOAuditReporter
{
    private Command $command;

    public function __construct(Command $command)
    {
        $this->command = $command;
    }

    /**
     * Display audit summary.
     */
    public function displaySummary(int $totalIssues, int $fixedIssues): void
    {
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');
        $this->command->info('📊 SEO Audit Summary');
        $this->command->info('━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━');

        $this->displaySummaryTable($totalIssues, $fixedIssues);

        if ($totalIssues === 0) {
            $this->command->info('✅ No SEO issues found! Your site is well optimized.');
        } elseif ($fixedIssues > 0) {
            $this->command->info("✅ Fixed {$fixedIssues} issues automatically.");

            if ($totalIssues > $fixedIssues) {
                $remaining = $totalIssues - $fixedIssues;
                $this->command->warn("⚠️  {$remaining} issues require manual attention.");
            }
        } else {
            $this->command->warn("⚠️  Found {$totalIssues} issues. Run with --fix to auto-fix.");
        }
    }

    /**
     * Display the details of the issues found for a model.
     */
    public function displayIssueDetails(SEOAuditResult $result): void
    {
        $this->displayModelHeader($result);
        $this->displayIssuesList($result->getIssues());
    }

    /**
     * Display duplicate routes information.
     *
     * @param  array<string, int>  $duplicates
     */
    public function displayDuplicateRoutes(array $duplicates, bool $showDetails): void
    {
        $duplicateCount = count($duplicates);

        if ($duplicateCount > 0) {
            $this->command->warn("  ⚠️  Found {$duplicateCount} duplicate routes");

            if ($showDetails) {
                foreach (array_keys($duplicates) as $duplicate) {
                    $this->command->line("     - {$duplicate}");
                }
            }
        }
    }

    /**
     * Display fix confirmation.
     */
    public function displayFixConfirmation(string $type, string $modelIdStr): void
    {
        $this->command->info("     ✅ Fixed {$type} #{$modelIdStr}");
    }

    /**
     * Display the summary table.
     */
    private function displaySummaryTable(int $totalIssues, int $fixedIssues): void
    {
        $this->command->table(
            ['Metric', 'Count'],
            [
                ['Total Issues Found', $totalIssues],
                ['Issues Fixed', $fixedIssues],
                ['Remaining Issues', $totalIssues - $fixedIssues],
            ]
        );
    }

    /**
     * Display the model header with issue information.
     */
    private function displayModelHeader(SEOAuditResult $result): void
    {
        $modelIdStr = $result->getModelId();
        $modelName = $result->getModelName();
        $type = $result->getModelType();

        $this->command->warn("  ⚠️  {$type} #{$modelIdStr}: {$modelName}");
    }

    /**
     * Display the list of issues.
     *
     * @param  array<int, string>  $issues
     */
    private function displayIssuesList(array $issues): void
    {
        foreach ($issues as $issue) {
            $this->command->line("     - {$issue}");
        }
    }
}
