<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Process\Process;

final class QualityAnalysisService
{
    /**
     * Run comprehensive code quality analysis.
     *
     * @return array<string, int|list<string>>
     */
    public function analyze(): array
    {
        $score = 0;
        $issues = [];

        try {
            $score += $this->runPhpmdAnalysis($issues);
            $score += $this->runPhpcpdAnalysis($issues);
        } catch (\Exception $e) {
            $issues[] = 'Code quality analysis failed: '.$e->getMessage();
        }

        return [
            'score' => min(100, $score),
            'max_score' => 100,
            'issues' => $issues,
            'category' => 'Code Quality',
        ];
    }

    /**
     * Run PHPMD analysis.
     *
     * @param  array<string>  $issues
     *
     * @psalm-return int<0, max>
     */
    private function runPhpmdAnalysis(array &$issues): int
    {
        if (! $this->commandExists('vendor/bin/phpmd')) {
            return 0;
        }

        $process = new Process(['./vendor/bin/phpmd', 'app', 'text', 'cleancode,codesize,controversial,design,naming,unusedcode']);
        $process->run();

        $output = $process->getOutput();
        $errorCount = substr_count(trim($output), "\n");

        if ($errorCount === 0) {
            return 50;
        }

        $issues[] = "PHPMD found {$errorCount} code quality issues.";

        return max(0, 50 - ($errorCount * 2));
    }

    /**
     * Run PHPCPD analysis.
     *
     * @param  array<string>  $issues
     *
     * @psalm-return int<0, max>
     */
    private function runPhpcpdAnalysis(array &$issues): int
    {
        if (! $this->commandExists('vendor/bin/phpcpd')) {
            return 0;
        }

        $process = new Process(['./vendor/bin/phpcpd', 'app']);
        $process->run();
        $output = $process->getOutput();

        if (str_contains($output, 'No clones found')) {
            return 50;
        }

        preg_match('/(\d+\.\d+)\% duplicated lines/', $output, $matches);
        $duplication = isset($matches[1]) ? (float) $matches[1] : 100.0;
        $issues[] = "PHPCPD found {$duplication}% duplicate code.";

        return max(0, 50 - (int) ($duplication * 5));
    }

    /**
     * Check if command exists.
     */
    private function commandExists(string $command): bool
    {
        return file_exists(base_path($command));
    }
}
