<?php

namespace Tests;

use PHPUnit\Framework\TestCase;

/**
 * Safe Laravel test base that preserves error handlers
 * to prevent PHPUnit risky test warnings.
 */
class SafeLaravelTest extends TestCase
{
    use \Tests\DatabaseSetup;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        // Don't modify error handlers to avoid PHPUnit warnings
    }

    public static function tearDownAfterClass(): void
    {
        parent::tearDownAfterClass();
        // Don't modify error handlers to avoid PHPUnit warnings
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpDatabase();
    }

    protected function tearDown(): void
    {
        // Rollback any remaining transactions
        while (\DB::transactionLevel() > 0) {
            \DB::rollBack();
        }

        // Clean up Mockery
        if (class_exists(\Mockery::class)) {
            \Mockery::close();
        }

        parent::tearDown();
    }

    /**
     * Test that SafeLaravelTest can be instantiated.
     */
    public function test_can_be_instantiated(): void
    {
        $this->assertInstanceOf(self::class, $this);
    }
}
