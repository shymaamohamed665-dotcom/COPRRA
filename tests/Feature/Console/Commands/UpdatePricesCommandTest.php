<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * @runTestsInSeparateProcesses
 */
class UpdatePricesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function update_prices_command_runs_successfully(): void
    {
        $this->artisan('prices:update')->assertExitCode(0);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function update_prices_command_runs_in_dry_run_mode(): void
    {
        $this->artisan('prices:update', ['--dry-run' => true])->assertExitCode(0);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function update_prices_command_filters_by_store(): void
    {
        $this->artisan('prices:update', ['--store' => 1])->assertExitCode(0);
    }

    #[Test]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function update_prices_command_filters_by_product(): void
    {
        $this->artisan('prices:update', ['--product' => 1])->assertExitCode(0);
    }
}
