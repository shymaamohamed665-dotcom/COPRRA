<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class DirectoryCleaner
{
    /**
     * @var array<int, string>
     */
    private array $removed = [];

    /**
     * @var array<int, string>
     */
    private array $failed = [];

    public function run(): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "Cleaning Up Problematic Directories\n";
        echo str_repeat('=', 60)."\n\n";

        $this->findAndRemoveProblematicDirs();

        $this->printSummary();
        $this->printNextSteps();

        if (count($this->removed) > 0) {
            echo "✓ Cleanup completed successfully!\n\n";
            exit(0);
        }
        echo "⚠ No directories were removed. Manual intervention may be required.\n\n";
        exit(1);
    }

    private function deleteDirectory(string $dir): bool
    {
        if (! file_exists($dir)) {
            return true;
        }

        if (! is_dir($dir)) {
            return unlink($dir);
        }

        $items = scandir($dir);
        if ($items === false) {
            return false;
        }

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $path = $dir.DIRECTORY_SEPARATOR.$item;

            if (is_dir($path)) {
                if (! $this->deleteDirectory($path)) {
                    return false;
                }
            } elseif (! unlink($path)) {
                return false;
            }
        }

        return rmdir($dir);
    }

    private function findAndRemoveProblematicDirs(): void
    {
        echo "Searching for problematic directories...\n\n";

        $problematicDirs = $this->scanForProblematicDirectories();

        if (empty($problematicDirs)) {
            echo "✓ No problematic directories found\n";

            return;
        }

        $this->displayProblematicDirectories($problematicDirs);
        $this->removeProblematicDirectories($problematicDirs);
    }

    /**
     * Scan directories for problematic paths
     *
     * @return array<int, string>
     */
    private function scanForProblematicDirectories(): array
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        $problematicDirs = [];

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                $path = $file->getPathname();
                $this->processDirectoryPath($path, $problematicDirs);
            }
        }

        return $this->finalizeProblematicList($problematicDirs);
    }

    /**
     * Process individual directory path for problematic patterns
     *
     * @param  array<int, string>  $problematicDirs
     */
    private function processDirectoryPath(string $path, array &$problematicDirs): void
    {
        if (! $this->isProblematicPath($path)) {
            return;
        }

        $topLevelDir = $this->extractTopLevelDirectory($path);
        if ($topLevelDir && ! in_array($topLevelDir, $problematicDirs)) {
            $problematicDirs[] = $topLevelDir;
        }
    }

    /**
     * Check if path contains problematic patterns
     */
    private function isProblematicPath(string $path): bool
    {
        return strpos($path, 'C:') !== false ||
               (strpos($path, 'Users') !== false && strpos($path, 'Desktop') !== false);
    }

    /**
     * Extract top-level problematic directory from path
     */
    private function extractTopLevelDirectory(string $path): ?string
    {
        $parts = explode(DIRECTORY_SEPARATOR, $path);
        $topLevel = '';

        foreach ($parts as $part) {
            if (strpos($part, 'C:') !== false) {
                $topLevel = $part;
                break;
            }
        }

        if (! $topLevel) {
            return null;
        }

        return $this->buildFullPathToTopLevel($path, $topLevel);
    }

    /**
     * Build full path to top-level directory
     */
    private function buildFullPathToTopLevel(string $path, string $topLevel): string
    {
        $fullPath = dirname($path);
        while (basename($fullPath) !== $topLevel && $fullPath !== '.') {
            $fullPath = dirname($fullPath);
        }

        return $fullPath;
    }

    /**
     * Finalize problematic directories list
     *
     * @param  array<int, string>  $problematicDirs
     * @return array<int, string>
     */
    private function finalizeProblematicList(array $problematicDirs): array
    {
        $problematicDirs = array_unique($problematicDirs);
        sort($problematicDirs);

        return $problematicDirs;
    }

    /**
     * Display found problematic directories
     *
     * @param  array<int, string>  $problematicDirs
     */
    private function displayProblematicDirectories(array $problematicDirs): void
    {
        echo 'Found '.count($problematicDirs)." problematic director(ies):\n\n";

        foreach ($problematicDirs as $dir) {
            echo "  - {$dir}\n";
        }

        echo "\nRemoving problematic directories...\n\n";
    }

    /**
     * Remove problematic directories
     *
     * @param  array<int, string>  $problematicDirs
     */
    private function removeProblematicDirectories(array $problematicDirs): void
    {
        foreach ($problematicDirs as $dir) {
            $this->attemptDirectoryRemoval($dir);
        }
    }

    /**
     * Attempt to remove a single directory
     */
    private function attemptDirectoryRemoval(string $dir): void
    {
        echo "Removing: {$dir} ... ";

        try {
            if ($this->deleteDirectory($dir)) {
                echo "✓ SUCCESS\n";
                $this->removed[] = $dir;
            } else {
                echo "✗ FAILED\n";
                $this->failed[] = $dir;
            }
        } catch (Exception $e) {
            echo '✗ ERROR: '.$e->getMessage()."\n";
            $this->failed[] = $dir;
        }
    }

    private function printSummary(): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "Cleanup Summary\n";
        echo str_repeat('=', 60)."\n\n";

        $this->printRemovedDirectories();
        $this->printFailedDirectories();
    }

    private function printRemovedDirectories(): void
    {
        echo 'Removed: '.count($this->removed)." director(ies)\n";
        foreach ($this->removed as $dir) {
            echo "  ✓ {$dir}\n";
        }
    }

    private function printFailedDirectories(): void
    {
        if (empty($this->failed)) {
            return;
        }

        echo "\nFailed: ".count($this->failed)." director(ies)\n";
        foreach ($this->failed as $dir) {
            echo "  ✗ {$dir}\n";
        }
    }

    private function printNextSteps(): void
    {
        echo "\n".str_repeat('=', 60)."\n";
        echo "Next Steps\n";
        echo str_repeat('=', 60)."\n\n";

        echo "1. Clear view cache:\n";
        echo "   php artisan view:clear\n\n";

        echo "2. Regenerate view cache:\n";
        echo "   php artisan view:cache\n\n";

        echo "3. Verify storage link:\n";
        echo "   php artisan storage:link\n\n";

        echo "4. Verify cleanup:\n";
        echo "   find . -type d -path \"*C:*\" 2>/dev/null\n\n";
    }
}

// Create and run the cleaner
$cleaner = new DirectoryCleaner;
$cleaner->run();
