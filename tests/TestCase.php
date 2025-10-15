<?php

namespace Tests;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

// Ensure traits are loaded even if autoloader misses them
require_once __DIR__.'/CreatesApplication.php';
require_once __DIR__.'/DatabaseSetup.php';
require_once __DIR__.'/EnhancedTestIsolation.php';

class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseSetup;
    use EnhancedTestIsolation;

    // Track if database has been set up to avoid redundant setup
    protected static bool $databaseSetUp = false;

    // Enable DB transactions for test isolation (IMPORTANT: Prevents interdependent tests!)
    // Each test runs in a transaction that is rolled back after completion
    protected array $connectionsToTransact = ['sqlite'];

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        // Apply enhanced isolation BEFORE parent setup to ensure clean state
        $this->setUpEnhancedIsolation();

        parent::setUp();

        // Skip database setup for tests running in separate processes
        // These tests typically don't need database access and separate process isolation
        // conflicts with database transactions
        if ($this->runTestsInSeparateProcesses()) {
            return;
        }

        // تأكد من أن المخطط موجود للاختبارات التي لا تستخدم RefreshDatabase
        // الميجريشن مع SQLite in-memory سريعة وتمنع أخطاء الجداول المفقودة
        if (! $this->usesRefreshDatabase() && ! \Illuminate\Support\Facades\Schema::hasTable('users')) {
            \Illuminate\Support\Facades\Artisan::call('migrate', [
                '--database' => env('DB_CONNECTION', 'sqlite'),
            ]);
        }

        // أنشئ الجداول الأساسية يدويًا بشكل idempotent لتغطية الجداول غير المُنشأة عبر الميجريشن
        // هذا يعمل سواءً استخدم الاختبار RefreshDatabase أم لا
        $this->setUpDatabase();

        static::$databaseSetUp = true;
    }

    /**
     * Detect whether the current test class uses RefreshDatabase trait.
     */
    protected function usesRefreshDatabase(): bool
    {
        $uses = array_keys(class_uses_recursive(static::class));

        return in_array(\Illuminate\Foundation\Testing\RefreshDatabase::class, $uses, true);
    }

    /**
     * Detect whether the current test class runs tests in separate processes.
     */
    protected function runTestsInSeparateProcesses(): bool
    {
        // Disable separate process execution globally to avoid incompatibilities
        // with current PHPUnit version and child process bootstrap.
        return false;
    }

    /**
     * Tear down the test environment.
     */
    protected function tearDown(): void
    {
        // Clean up database
        $this->tearDownDatabase();

        // Clean up Mockery
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }

        parent::tearDown();

        // Apply enhanced isolation cleanup AFTER parent teardown
        $this->tearDownEnhancedIsolation();
    }
}
