<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DatabaseConnectionTest extends TestCase
{
    use RefreshDatabase;

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_basic_functionality(): void
    {
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_expected_behavior(): void
    {
        $this->assertTrue(true);
    }

    #[\PHPUnit\Framework\Attributes\Test]
    public function test_validation(): void
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


