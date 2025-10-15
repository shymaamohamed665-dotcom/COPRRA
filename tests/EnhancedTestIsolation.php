<?php

declare(strict_types=1);

namespace Tests;

/**
 * Enhanced Test Isolation Trait
 *
 * Prevents side-effects and cross-contamination between tests by:
 * - Backing up and restoring superglobals ($_ENV, $_SERVER)
 * - Clearing all caches before/after each test
 * - Cleaning up temporary files
 * - Resetting service container singletons
 * - Clearing configuration cache
 *
 * Usage: Add this trait to TestCase.php or individual test classes
 */
trait EnhancedTestIsolation
{
    /**
     * Backup of $_ENV superglobal before test.
     */
    protected static array $envBackup = [];

    /**
     * Backup of $_SERVER superglobal before test.
     */
    protected static array $serverBackup = [];

    /**
     * Backup of application config before test.
     */
    protected static array $configBackup = [];

    /**
     * Temporary files/directories created during test.
     */
    protected array $temporaryPaths = [];

    /**
     * Set up enhanced isolation before each test.
     */
    protected function setUpEnhancedIsolation(): void
    {
        // Backup superglobals to prevent pollution
        $this->backupSuperglobals();

        // Clear all caches to ensure clean state
        $this->clearAllCaches();

        // Reset service container to default state
        $this->resetServiceContainer();

        // Backup current configuration
        $this->backupConfiguration();

        // Track temp directory for cleanup
        $this->temporaryPaths = [];
    }

    /**
     * Tear down enhanced isolation after each test.
     */
    protected function tearDownEnhancedIsolation(): void
    {
        // Restore superglobals to prevent pollution
        $this->restoreSuperglobals();

        // Clear all caches again to clean up test artifacts
        $this->clearAllCaches();

        // Restore original configuration
        $this->restoreConfiguration();

        // Clean up temporary files/directories
        $this->cleanupTemporaryPaths();

        // Reset service container again
        $this->resetServiceContainer();
    }

    /**
     * Backup $_ENV and $_SERVER superglobals.
     */
    protected function backupSuperglobals(): void
    {
        static::$envBackup = $_ENV;
        static::$serverBackup = $_SERVER;
    }

    /**
     * Restore $_ENV and $_SERVER superglobals.
     */
    protected function restoreSuperglobals(): void
    {
        // Restore only if backups exist
        if (! empty(static::$envBackup)) {
            $_ENV = static::$envBackup;
        }

        if (! empty(static::$serverBackup)) {
            $_SERVER = static::$serverBackup;
        }
    }

    /**
     * Clear all application caches.
     */
    protected function clearAllCaches(): void
    {
        try {
            // Clear application cache
            if (method_exists($this, 'app') && $this->app) {
                if ($this->app->bound('cache')) {
                    $this->app['cache']->flush();
                }

                // Clear config cache
                if ($this->app->bound('config')) {
                    $this->app['config']->set('cache.stores.array.store', []);
                }

                // Clear view cache
                if (method_exists(\Illuminate\Support\Facades\View::class, 'getFinder')) {
                    try {
                        \Illuminate\Support\Facades\View::getFinder()->flush();
                    } catch (\Throwable $e) {
                        // Silently fail - view finder might not be available
                    }
                }
            }

            // Use Cache facade if available
            if (class_exists(\Illuminate\Support\Facades\Cache::class)) {
                try {
                    \Illuminate\Support\Facades\Cache::flush();
                } catch (\Throwable $e) {
                    // Silently fail - cache might not be fully configured
                }
            }

            // Clear opcache if available
            if (function_exists('opcache_reset')) {
                @opcache_reset();
            }
        } catch (\Throwable $e) {
            // Silently fail - some cache operations may not be available in all contexts
        }
    }

    /**
     * Reset the service container to default state.
     */
    protected function resetServiceContainer(): void
    {
        try {
            if (method_exists($this, 'app') && $this->app) {
                // Flush resolved instances (singletons)
                if (method_exists($this->app, 'forgetInstances')) {
                    $this->app->forgetInstances();
                }

                // Refresh key services that might cache state
                $servicesToRefresh = [
                    'cache',
                    'cache.store',
                    'config',
                    'db',
                    'db.connection',
                    'events',
                    'files',
                    'log',
                    'queue',
                    'redis',
                    'session',
                    'session.store',
                    'view',
                ];

                foreach ($servicesToRefresh as $service) {
                    if ($this->app->bound($service)) {
                        try {
                            $this->app->forgetInstance($service);
                        } catch (\Throwable $e) {
                            // Silently continue
                        }
                    }
                }
            }
        } catch (\Throwable $e) {
            // Silently fail - service container might not be available
        }
    }

    /**
     * Backup current configuration.
     */
    protected function backupConfiguration(): void
    {
        try {
            if (method_exists($this, 'app') && $this->app && $this->app->bound('config')) {
                static::$configBackup = $this->app['config']->all();
            }
        } catch (\Throwable $e) {
            // Silently fail
        }
    }

    /**
     * Restore original configuration.
     */
    protected function restoreConfiguration(): void
    {
        try {
            if (! empty(static::$configBackup) && method_exists($this, 'app') && $this->app && $this->app->bound('config')) {
                foreach (static::$configBackup as $key => $value) {
                    $this->app['config']->set($key, $value);
                }
            }
        } catch (\Throwable $e) {
            // Silently fail
        }
    }

    /**
     * Register a temporary path for automatic cleanup.
     */
    protected function trackTemporaryPath(string $path): void
    {
        $this->temporaryPaths[] = $path;
    }

    /**
     * Clean up all registered temporary paths.
     */
    protected function cleanupTemporaryPaths(): void
    {
        foreach ($this->temporaryPaths as $path) {
            try {
                if (is_file($path)) {
                    @unlink($path);
                } elseif (is_dir($path)) {
                    $this->deleteDirectory($path);
                }
            } catch (\Throwable $e) {
                // Silently continue
            }
        }

        $this->temporaryPaths = [];
    }

    /**
     * Recursively delete a directory.
     */
    protected function deleteDirectory(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        try {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
                \RecursiveIteratorIterator::CHILD_FIRST
            );

            foreach ($iterator as $file) {
                if ($file->isDir()) {
                    @rmdir($file->getPathname());
                } else {
                    @unlink($file->getPathname());
                }
            }

            @rmdir($dir);
        } catch (\Throwable $e) {
            // Silently fail
        }
    }

    /**
     * Get a clean temporary directory for this test.
     */
    protected function getTemporaryDirectory(string $prefix = 'test_'): string
    {
        $tempDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.$prefix.uniqid('', true);
        @mkdir($tempDir, 0755, true);
        $this->trackTemporaryPath($tempDir);

        return $tempDir;
    }
}
