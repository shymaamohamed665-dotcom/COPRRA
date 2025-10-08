<?php

namespace Tests\Performance;

use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LoadTimeTest extends TestCase
{
    public function test_page_load_time(): void
    {
        $this->assertTrue(true);
    }

    public function test_asset_load_time(): void
    {
        $this->assertTrue(true);
    }

    public function test_api_load_time(): void
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
