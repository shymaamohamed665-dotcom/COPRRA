<?php

declare(strict_types=1);

namespace App\Services;

use Symfony\Component\Process\Process;

final class SecurityAnalysisService
{
    /**
     * Run comprehensive security analysis.
     *
     * @return (int|string|string[])[]
     *
     * @psalm-return array{score: int, max_score: 100, issues: list<string>, category: 'Security'}
     */
    public function analyze(): array
    {
        $score = 0;
        $issues = [];

        try {
            $score += $this->checkDependencies($issues);
            $score += $this->checkEnvironmentFile($issues);
            $score += $this->checkDebugMode($issues);
            $score += $this->checkHttpsConfiguration($issues);
            $score += $this->checkSecurityMiddleware($issues);
        } catch (\Exception $e) {
            $issues[] = 'Security analysis failed: '.$e->getMessage();
        }

        return [
            'score' => $score,
            'max_score' => 100,
            'issues' => $issues,
            'category' => 'Security',
        ];
    }

    /**
     * Check for outdated dependencies.
     *
     * @param  list<string>  $issues
     *
     * @psalm-return 0|30
     */
    private function checkDependencies(array &$issues): int
    {
        $process = new Process(['composer', 'outdated', '--direct']);
        $process->run();

        if (! $process->isSuccessful()) {
            return 0;
        }

        $outdated = $process->getOutput();
        if (
            in_array(trim($outdated), ['', '0'], true) ||
            str_contains($outdated, 'No direct dependencies')
        ) {
            return 30;
        }

        $issues[] = 'Outdated dependencies found. Consider running "composer update".';

        return 0;
    }

    /**
     * Check if .env.example file exists.
     *
     * @param  list<string>  $issues
     *
     * @psalm-return 0|10
     */
    private function checkEnvironmentFile(array &$issues): int
    {
        if (file_exists(base_path('.env.example'))) {
            return 10;
        }

        $issues[] = '.env.example file missing';

        return 0;
    }

    /**
     * Check if debug mode is disabled.
     *
     * @param  list<string>  $issues
     *
     * @psalm-return 0|20
     */
    private function checkDebugMode(array &$issues): int
    {
        if (config('app.debug') === false) {
            return 20;
        }

        $issues[] = 'Debug mode is enabled (should be false in production)';

        return 0;
    }

    /**
     * Check if HTTPS is configured.
     *
     * @param  list<string>  $issues
     *
     * @psalm-return 0|20
     */
    private function checkHttpsConfiguration(array &$issues): int
    {
        $appUrl = config('app.url');
        if ($appUrl && is_string($appUrl) && str_starts_with($appUrl, 'https')) {
            return 20;
        }

        $issues[] = 'HTTPS not configured in APP_URL';

        return 0;
    }

    /**
     * Check if SecurityHeadersMiddleware is registered.
     *
     * @param  list<string>  $issues
     *
     * @psalm-return 0|20
     */
    private function checkSecurityMiddleware(array &$issues): int
    {
        if ($this->isMiddlewareRegistered(\App\Http\Middleware\SecurityHeadersMiddleware::class)) {
            return 20;
        }

        $issues[] = 'SecurityHeadersMiddleware is not registered globally in app/Http/Kernel.php';

        return 0;
    }

    /**
     * Check if middleware is registered in the kernel.
     */
    private function isMiddlewareRegistered(string $middlewareClass): bool
    {
        try {
            // Check if class exists first
            if (! class_exists($middlewareClass)) {
                return false;
            }

            // For Laravel 10+, we need to check the kernel file directly
            $kernelFile = app_path('Http/Kernel.php');
            if (! file_exists($kernelFile)) {
                return false;
            }

            $kernelContent = file_get_contents($kernelFile);
            $shortClassName = class_basename($middlewareClass);

            if ($kernelContent === false) {
                return false;
            }

            // Check if middleware is registered in any of the arrays
            return str_contains($kernelContent, $middlewareClass) ||
                str_contains($kernelContent, $shortClassName);
        } catch (\Exception) {
            return false;
        }
    }
}
