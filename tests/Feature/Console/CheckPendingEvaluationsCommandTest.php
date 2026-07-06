<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckPendingEvaluationsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_evaluations_command_runs_successfully()
    {
        $this->artisan('rh:check-pending-evaluations')
            ->assertExitCode(0);
    }
}
