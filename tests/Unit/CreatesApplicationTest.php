<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class CreatesApplicationTest extends TestCase
{
    public function test_application_can_be_created(): void
    {
        $this->assertNotNull($this->app);
        $this->assertInstanceOf(\Illuminate\Foundation\Application::class, $this->app);
    }
}
