<?php

declare(strict_types=1);

namespace App\Services\AI;

use Illuminate\Contracts\Process\ProcessResult;

final class HealthScoreCalculator
{
    /**
     * Calculate health score based on rule type and process result.
     */
    public function calculate(string $ruleId, ProcessResult $result): int
    {
        if (! $result->successful()) {
            return 0;
        }

        $output = $result->output();
        $outputString = is_string($output) ? $output : '';

        return match ($ruleId) {
            'code_quality' => $this->calculateCodeQualityScore($outputString),
            'test_coverage' => $this->calculateTestCoverageScore($outputString),
            'security_scan' => $this->calculateSecurityScore($outputString),
            'performance' => $this->calculatePerformanceScore($outputString),
            'memory_usage' => $this->calculateMemoryScore($outputString),
            default => 100,
        };
    }

    /**
     * Calculate code quality score from PHPStan output.
     */
    private function calculateCodeQualityScore(string $output): int
    {
        if (str_contains($output, 'No errors')) {
            return 100;
        }

        $errorCount = substr_count($output, 'ERROR');
        $warningCount = substr_count($output, 'WARNING');
        $totalIssues = $errorCount + $warningCount;

        return max(0, 100 - ($totalIssues * 5));
    }

    /**
     * Calculate test coverage score from coverage output.
     */
    private function calculateTestCoverageScore(string $output): int
    {
        if (preg_match('/(\d+)%/', $output, $matches)) {
            return (int) $matches[1];
        }

        return 0;
    }

    /**
     * Calculate security score from audit output.
     */
    private function calculateSecurityScore(string $output): int
    {
        if (str_contains($output, 'No security vulnerabilities')) {
            return 100;
        }

        $vulnerabilityCount = substr_count($output, 'vulnerability');

        return max(0, 100 - ($vulnerabilityCount * 20));
    }

    /**
     * Calculate performance score from test results.
     */
    private function calculatePerformanceScore(string $output): int
    {
        return str_contains($output, 'PASS') ? 100 : 50;
    }

    /**
     * Calculate memory usage score from memory output.
     */
    private function calculateMemoryScore(string $output): int
    {
        if (preg_match('/(\d+)MB/', $output, $matches)) {
            $memoryUsage = (int) $matches[1];
            $maxMemory = 512;

            return (int) max(0, 100 - ($memoryUsage / $maxMemory * 100));
        }

        return 100;
    }
}
