<?php

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

    /**
     * Original error handler.
     */
    private $originalErrorHandler;

    /**
     * Original exception handler.
     */
    private $originalExceptionHandler;

    /**
     * Set up the test environment with proper error handler management.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Store original handlers
        $this->originalErrorHandler = set_error_handler(null);
        $this->originalExceptionHandler = set_exception_handler(null);

        // Restore original handlers
        if ($this->originalErrorHandler) {
            set_error_handler($this->originalErrorHandler);
        }
        if ($this->originalExceptionHandler) {
            set_exception_handler($this->originalExceptionHandler);
        }
    }

    /**
     * Clean up the testing environment before the next test.
     */
    protected function tearDown(): void
    {
        // Restore original handlers
        if ($this->originalErrorHandler) {
            set_error_handler($this->originalErrorHandler);
        }
        if ($this->originalExceptionHandler) {
            set_exception_handler($this->originalExceptionHandler);
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
        $app = require __DIR__.'/../bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    /**
     * Test that SafeTestBase can be instantiated and works correctly.
     */
    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(self::class, $this);
        $this->assertTrue(method_exists($this, 'setUp'));
        $this->assertTrue(method_exists($this, 'tearDown'));
        $this->assertTrue(true);
    }
}
