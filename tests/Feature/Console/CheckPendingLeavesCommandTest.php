<?php

namespace Tests\Feature\Console;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckPendingLeavesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_pending_leaves_command_runs_successfully()
    {
        $this->artisan('rh:check-pending-leaves')
            ->assertExitCode(0);
    }
}
