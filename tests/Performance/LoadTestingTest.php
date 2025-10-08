<?php

namespace Tests\Performance;

use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LoadTestingTest extends TestCase
{
    public function test_load_testing_basic(): void
    {
        $this->assertTrue(true);
    }

    public function test_load_testing_medium(): void
    {
        $this->assertTrue(true);
    }

    public function test_load_testing_high(): void
    {
        $this->assertTrue(true);
    }

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }
}
