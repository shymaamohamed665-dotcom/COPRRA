<?php

namespace Tests;

/**
 * Safe base class for middleware tests that avoids full Laravel bootstrap
 * to prevent error/exception handler modifications, while providing DB setup.
 */
class SafeMiddlewareTestBase extends SafeTestBase
{
    use DatabaseSetup;

    /**
     * Set up the test environment with DB setup, without full app bootstrap.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    /**
     * Tear down the test environment with DB cleanup.
     */
    protected function tearDown(): void
    {
        $this->tearDownDatabase();
        parent::tearDown();
    }
}
