<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class UpdatePricesCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function update_prices_command_runs_successfully(): void
    {
        $this->artisan('prices:update')->assertExitCode(0);
    }

    #[Test]
    public function update_prices_command_runs_in_dry_run_mode(): void
    {
        $this->artisan('prices:update', ['--dry-run' => true])->assertExitCode(0);
    }

    #[Test]
    public function update_prices_command_filters_by_store(): void
    {
        $this->artisan('prices:update', ['--store' => 1])->assertExitCode(0);
    }

    #[Test]
    public function update_prices_command_filters_by_product(): void
    {
        $this->artisan('prices:update', ['--product' => 1])->assertExitCode(0);
    }
}
