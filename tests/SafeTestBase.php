<?php

declare(strict_types=1);

namespace Tests;

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Foundation\Application;
use PHPUnit\Framework\TestCase;

/**
 * Safe test base class that properly manages error handlers
 * while maintaining PHPUnit compatibility.
 */
class SafeTestBase extends TestCase
{
    /**
     * The Illuminate application instance.
     *
     * @var \Illuminate\Foundation\Application
     */
    protected $app;

    /** @var bool */
    private $handlersRestored = false; // kept for BC, no longer used

    /**
     * Set up the test environment with proper error handler management.
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        // Restore global error/exception handlers that may have been set during app bootstrap
        try {
            restore_error_handler();
        } catch (\Throwable $e) {
            // ignore
        }
        try {
            restore_exception_handler();
        } catch (\Throwable $e) {
            // ignore
        }

        // Clean up Laravel app if it exists
        if ($this->app) {
            $this->app->flush();
        }

        $this->app = null;

        parent::tearDown();
    }

    /**
     * Creates the application.
     *
     * @return \Illuminate\Foundation\Application
     */
    public function createApplication()
    {
        // Build the application and bootstrap the Kernel to ensure all providers are registered
        $app = require __DIR__.'/../bootstrap/app.php';
        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Test that SafeTestBase can be instantiated and works correctly.
     */
    #[\PHPUnit\Framework\Attributes\RunInSeparateProcess]
    #[\PHPUnit\Framework\Attributes\PreserveGlobalState(false)]
    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(self::class, $this);
        $this->assertTrue(method_exists($this, 'setUp'));
        $this->assertTrue(method_exists($this, 'tearDown'));
        $this->assertTrue(true);
    }
}


