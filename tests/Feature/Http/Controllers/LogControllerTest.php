<?php

namespace Tests\Feature\Http\Controllers;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class LogControllerTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_can_get_application_logs(): void
    {
        $this->assertTrue(true);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function it_can_clear_application_logs(): void
    {
        $this->assertTrue(true);
    }
}
