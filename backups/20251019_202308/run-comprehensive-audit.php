<?php

declare(strict_types=1);

namespace App\Console\Commands;

/**
 * Comprehensive Audit & Analysis Script
 * Executes all phases of the audit roadmap systematically
 */
class ComprehensiveAuditor
{
    /**
     * @var array<int, string>
     */
    private array $findings = [];

    /**
     * @var array<int, string>
     */
    private array $fixes = [];

    /**
     * @var array<int, string>
     */
    private array $recommendations = [];

    private string $reportPath;

    private string $timestamp;

    public function __construct()
    {
        $this->timestamp = date('Ymd_His');
        $this->reportPath = "reports/comprehensive-audit-{$this->timestamp}.md";

        if (! is_dir('reports')) {
            mkdir('reports', 0755, true);
        }
    }

    public function run(): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "COMPREHENSIVE AUDIT & ANALYSIS\n";
        echo str_repeat('=', 60)."\n\n";

        $this->phase1CoreQuality();
        $this->runArtisanCommand('clear:all', 'Clearing caches');

        $this->phase1InitialChecks();
        $this->phase2DeepAnalysis();
        $this->phase3EnvironmentValidation();
        $this->phase4AdvancedTesting();

        $this->generateReport();
    }

    private function phase1CoreQuality(): void
    {
        echo "\n";
        echo "=== PHASE 1: Core Code Quality & Baseline Checks ===\n";
        echo "\n";

        // 1.1: Verify Tools Installation
        echo "Step 1.1: Verifying QA Tools Installation...\n";
        $tools = [
            'phpinsights' => 'vendor/bin/phpinsights',
            'composer-unused' => 'vendor/bin/composer-unused',
            'phpstan' => 'vendor/bin/phpstan',
            'phpunit' => 'vendor/bin/phpunit',
        ];

        foreach ($tools as $name => $path) {
            if (file_exists($path)) {
                echo "  ✓ {$name}: INSTALLED\n";
            } else {
                $this->findings[] = "Missing tool: {$name}";
                echo "  ✗ {$name}: NOT FOUND\n";
            }
        }

        // 1.2: Check Code Formatting
        echo "\nStep 1.2: Checking Code Formatting...\n";
        $this->runCommand(
            'Code Formatting (Pint)',
            'vendor/bin/pint --test 2>&1',
            "reports/pint-{$this->timestamp}.txt"
        );

        // 1.3: Check Code Duplication
        echo "\nStep 1.3: Checking Code Duplication...\n";
        if (file_exists('vendor/bin/phpcpd')) {
            $this->runCommand(
                'Code Duplication',
                'vendor/bin/phpcpd app --min-lines=3 --min-tokens=40 2>&1',
                "reports/duplication-{$this->timestamp}.txt"
            );
        } else {
            echo "  ⚠ PHPCPD not installed\n";
            $this->recommendations[] = 'Install sebastian/phpcpd for duplication detection';
        }

        // 1.4: Check Code Complexity
        echo "\nStep 1.4: Checking Code Complexity...\n";
        $command = 'vendor/bin/phpinsights analyse app --no-interaction --format=json 2>&1';
        $this->runCommand(
            'Code Complexity (PHP Insights)',
            $command,
            "reports/phpinsights-{$this->timestamp}.json"
        );
    }

    private function phase1InitialChecks(): void
    {
        $this->log("\n<info>Starting Phase 1: Initial Checks...</info>");

        $this->runArtisanCommand('code:analyse', 'Initial Checks');
        $this->log('<info>Phase 1: Initial Checks Completed.</info>');
    }

    private function phase2DeepAnalysis(): void
    {
        $this->log("\n<info>Starting Phase 2: Deep Code Analysis...</info>");

        $this->runArtisanCommand('code:analyse', 'Deep Code Analysis');
        $this->log('<info>Phase 2: Deep Code Analysis Completed.</info>');
    }

    private function phase3EnvironmentValidation(): void
    {
        $this->log("\n<info>Starting Phase 3: Environment Validation...</info>");

        $this->runArtisanCommand('config:validate', 'Configuration Validation');
        $this->log('<info>Phase 3: Environment Validation Completed.</info>');
    }

    private function phase4AdvancedTesting(): void
    {
        $this->log("\n<info>Starting Phase 4: Advanced Testing...</info>");

        $this->runArtisanCommand('test:security', 'Security Testing');
        $this->log('<info>Phase 4: Advanced Testing Completed.</info>');
    }

    private function runCommand(string $name, string $command, string $outputFile): void
    {
        $output = shell_exec($command);
        file_put_contents($outputFile, $output);

        $exitCode = 0;
        exec($command, $outputArray, $exitCode);

        if ($exitCode === 0) {
            echo "  ✓ {$name}: PASS\n";
        } else {
            echo "  ⚠ {$name}: Issues found (see {$outputFile})\n";
            $this->findings[] = "{$name}: Issues detected";
        }
    }

    /**
     * @return array<string>
     */
    private function findProblematicDirectories(): array
    {
        $problematic = [];

        // Find directories with Windows paths
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $path = $file->getPathname();
                if (
                    strpos($path, 'C:') !== false ||
                    strpos($path, 'Users') !== false
                ) {
                    $problematic[] = $path;
                }
            }
        }

        return $problematic;
    }

    private function checkComposerPackage(string $package): bool
    {
        $composerJson = json_decode(file_get_contents('composer.json'), true);

        return isset($composerJson['require'][$package]) || isset($composerJson['require-dev'][$package]);
    }

    private function checkSecurityBestPractices(): void
    {
        $checks = [
            '.env' => 'Environment file exists',
            'config/app.php' => 'App configuration exists',
            'config/database.php' => 'Database configuration exists',
        ];

        foreach ($checks as $file => $description) {
            if (file_exists($file)) {
                echo "  ✓ {$description}\n";
            } else {
                echo "  ✗ {$description} - NOT FOUND\n";
                $this->findings[] = "Missing: {$file}";
            }
        }
    }

    private function generateFinalReport(): void
    {
        $report = "# Comprehensive Audit Report\n\n";
        $report .= '**Generated:** '.date('Y-m-d H:i:s')."\n\n";
        $report .= "---\n\n";

        $report .= "## Executive Summary\n\n";
        $report .= "This comprehensive audit covered:\n";
        $report .= "- Core Code Quality & Baseline Checks\n";
        $report .= "- Deep Analysis & Testing\n";
        $report .= "- Environment & Integration Validation\n";
        $report .= "- Advanced Performance & Security Testing\n\n";

        $report .= "---\n\n";

        $report .= "## Issues Detected\n\n";
        if (count($this->findings) > 0) {
            foreach ($this->findings as $finding) {
                $report .= "- {$finding}\n";
            }
        } else {
            $report .= "✓ No critical issues detected\n";
        }
        $report .= "\n";

        $report .= "## Fixes Applied\n\n";
        if (count($this->fixes) > 0) {
            foreach ($this->fixes as $fix) {
                $report .= "- {$fix}\n";
            }
        } else {
            $report .= "No automated fixes applied in this audit run.\n";
        }
        $report .= "\n";

        $report .= "## Recommendations\n\n";
        if (count($this->recommendations) > 0) {
            foreach ($this->recommendations as $recommendation) {
                $report .= "- {$recommendation}\n";
            }
        } else {
            $report .= "✓ No additional recommendations\n";
        }
        $report .= "\n";

        $report .= "---\n\n";
        $report .= "## Detailed Reports\n\n";
        $report .= "Individual reports have been saved in the `reports/` directory:\n\n";
        $report .= "- Code Formatting: `reports/pint-{$this->timestamp}.txt`\n";
        $report .= "- PHPStan Analysis: `reports/phpstan-{$this->timestamp}.txt`\n";
        $report .= "- Security Audit: `reports/composer-audit-{$this->timestamp}.txt`\n";
        $report .= "- Test Results: `reports/phpunit-{$this->timestamp}.txt`\n";
        $report .= "- And more...\n\n";

        file_put_contents($this->reportPath, $report);
    }
}

// Run the audit
$auditor = new ComprehensiveAuditor;
$auditor->run();
