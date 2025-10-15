<?php

namespace Tests\Feature\Console\Commands;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Process;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AgentProposeFixCommandTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function agent_propose_fix_command_runs_successfully_with_style_option(): void
    {
        Process::fake();
        $this->artisan('agent:propose-fix', ['--type' => 'style'])->assertExitCode(0);
    }

    #[Test]
    public function agent_propose_fix_command_runs_successfully_with_analysis_option(): void
    {
        Process::fake();
        $this->artisan('agent:propose-fix', ['--type' => 'analysis'])->assertExitCode(0);
    }

    #[Test]
    public function agent_propose_fix_command_handles_unsupported_type(): void
    {
        $this->artisan('agent:propose-fix', ['--type' => 'invalid'])->assertExitCode(1);
    }
}
