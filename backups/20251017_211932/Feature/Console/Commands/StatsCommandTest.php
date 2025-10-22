<?php

declare(strict_types=1);

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class StatsCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function stats_command_runs_successfully_without_detailed_option(): void
    {
        $this->artisan('stats')->assertExitCode(0);
    }

    #[Test]
    public function stats_command_runs_successfully_with_detailed_option(): void
    {
        $this->artisan('stats', ['--detailed' => true])->assertExitCode(0);
    }

    #[Test]
    public function stats_command_displays_correct_basic_stats(): void
    {
        $this->artisan('stats')
            ->expectsOutputToContain('Basic Stats')
            ->assertExitCode(0);
    }

    #[Test]
    public function stats_command_displays_detailed_stats_when_requested(): void
    {
        $this->artisan('stats', ['--detailed' => true])
            ->expectsOutputToContain('Detailed Stats')
            ->assertExitCode(0);
    }

    #[Test]
    public function stats_command_handles_empty_database(): void
    {
        $this->artisan('stats')->assertExitCode(0);
    }

    #[Test]
    public function stats_command_handles_detailed_stats_with_empty_database(): void
    {
        $this->artisan('stats', ['--detailed' => true])->assertExitCode(0);
    }

    #[Test]
    public function stats_command_displays_recent_activity(): void
    {
        $this->artisan('stats')
            ->expectsOutputToContain('Recent Activity')
            ->assertExitCode(0);
    }

    #[Test]
    public function stats_command_displays_top_categories(): void
    {
        $this->artisan('stats', ['--detailed' => true])
            ->expectsOutputToContain('Top Categories')
            ->assertExitCode(0);
    }
}
