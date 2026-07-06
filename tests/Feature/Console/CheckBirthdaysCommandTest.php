<?php

namespace Tests\Feature\Console;

use App\Models\RH\Employee\Employee;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckBirthdaysCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_birthday_command_runs_successfully()
    {
        $this->artisan('rh:check-birthdays')
            ->assertExitCode(0);
    }
}
