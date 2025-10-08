<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use DatabaseSetup;

    /**
     * Set up the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
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

        // Restore error and exception handlers to prevent risky test warnings
        restore_error_handler();
        restore_exception_handler();

        parent::tearDown();
    }
}
