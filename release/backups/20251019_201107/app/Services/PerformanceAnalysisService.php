<?php

declare(strict_types=1);

namespace App\Services;

final class PerformanceAnalysisService
{
    /**
     * Run comprehensive performance analysis.
     *
     * @return array<string, int|list<string>>
     */
    public function analyze(): array
    {
        $score = 0;
        $issues = [];

        try {
            $score += $this->checkCacheConfiguration($issues);
            $score += $this->checkDatabaseIndexes($issues);
            $score += $this->checkAssetCompilation($issues);
            $score += $this->checkQueueConfiguration($issues);
        } catch (\Exception $e) {
            $issues[] = 'Performance analysis failed: '.$e->getMessage();
        }

        return [
            'score' => $score,
            'max_score' => 100,
            'issues' => $issues,
            'category' => 'Performance',
        ];
    }

    /**
     * Check cache configuration.
     *
     * @param  array<string>  $issues
     *
     * @psalm-return 0|25
     */
    private function checkCacheConfiguration(array &$issues): int
    {
        if (config('cache.default') !== 'file') {
            return 25;
        }

        $issues[] = 'Using file cache (consider Redis or Memcached for production)';

        return 0;
    }

    /**
     * Check database indexes.
     *
     * @param  array<string>  $issues
     *
     * @psalm-return 0|25
     */
    private function checkDatabaseIndexes(array &$issues): int
    {
        $migrationFiles = glob(database_path('migrations/*.php'));
        if ($migrationFiles === false) {
            $migrationFiles = [];
        }
        foreach ($migrationFiles as $file) {
            $content = file_get_contents($file);
            if ($content !== false && (str_contains($content, '->index(') || str_contains($content, '->unique('))) {
                return 25;
            }
        }

        $issues[] = 'No database indexes found in migrations';

        return 0;
    }

    /**
     * Check asset compilation.
     *
     * @param  array<string>  $issues
     *
     * @psalm-return 0|25
     */
    private function checkAssetCompilation(array &$issues): int
    {
        if (file_exists(public_path('build/manifest.json'))) {
            return 25;
        }

        $issues[] = 'No compiled assets found (run npm run build)';

        return 0;
    }

    /**
     * Check queue configuration.
     *
     * @param  array<string>  $issues
     *
     * @psalm-return 0|25
     */
    private function checkQueueConfiguration(array &$issues): int
    {
        if (config('queue.default') !== 'sync') {
            return 25;
        }

        $issues[] = 'Using sync queue (consider database or Redis queue for production)';

        return 0;
    }
}
