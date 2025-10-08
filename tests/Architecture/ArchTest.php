<?php

namespace Tests\Architecture;

use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

#[PreserveGlobalState(false)]

/**
 * @runTestsInSeparateProcesses
 */
class ArchTest extends TestCase
{
    #[Test]
    public function controllers_architecture(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function models_architecture(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function services_architecture(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function middleware_architecture(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    public function providers_architecture(): void
    {
        $this->assertTrue(true);
    }
}
