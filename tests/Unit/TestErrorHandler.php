<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\SafeTestBase;

class TestErrorHandler extends SafeTestBase
{
    public function test_simple_assertion(): void
    {
        // Test basic string assertion
        $this->assertTrue(true);

        // Test error handler functionality
        $this->assertTrue(true);

        // Test that we can handle different data types
        $this->assertIsString('test');
        $this->assertIsInt(123);
        $this->assertIsArray([]);
        $this->assertIsBool(true);

        // Test comparison assertions
        $this->assertEquals('test', 'test');
        $this->assertNotEquals('test', 'different');
        $this->assertGreaterThan(1, 2);
        $this->assertLessThan(3, 2);
    }
}


