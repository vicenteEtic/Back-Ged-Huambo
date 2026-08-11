<?php

namespace Tests\Feature\RH\Career;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Career\RetirementEligibility;
use App\Models\RH\Career\RetirementProcess;
use App\Models\RH\Career\PostRetirementHistory;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class RetirementTest extends RhTestCase
{
    protected Employee $employee;

    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'user_id' => $this->user->id,
            'date_of_birth' => now()->subYears(30)->toDateString(),
        ]);
    }

    public function test_can_check_eligibility()
    {
        RetirementEligibility::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/retirement/eligibility/' . $this->employee->id);
        $response->assertStatus(200);
    }

    public function test_can_list_processes()
    {
        RetirementProcess::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/retirement/processes');
        $response->assertStatus(200);
    }

    public function test_can_create_process()
    {
        $data = RetirementProcess::factory()->make([
            'employee_id' => $this->employee->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/retirement/processes', $data);
        $response->assertStatus(201);
    }

    public function test_can_show_process()
    {
        $process = RetirementProcess::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/retirement/processes/' . $process->id);
        $response->assertStatus(200);
    }

    public function test_can_update_process()
    {
        $process = RetirementProcess::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $data = RetirementProcess::factory()->make([
            'employee_id' => $this->employee->id,
            'status' => 'approved',
            'approved_by' => $this->user->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/retirement/processes/' . $process->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy_process()
    {
        $process = RetirementProcess::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/retirement/processes/' . $process->id);
        $response->assertStatus(204);
    }

    public function test_can_get_history_by_employee()
    {
        $process = RetirementProcess::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        PostRetirementHistory::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
            'retirement_process_id' => $process->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/retirement/processes/by-employee/' . $this->employee->id);
        $response->assertStatus(200);
    }

    public function test_upcoming_returns_employees_approaching_retirement()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $eligible = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_of_birth' => now()->subYears(62)->toDateString(),
            'status' => 'active',
        ]);
        $near = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_of_birth' => now()->subYears(57)->toDateString(),
            'status' => 'active',
        ]);
        $far = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_of_birth' => now()->subYears(50)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->getJsonAuth('/api/rh/retirement/upcoming');

        $response->assertStatus(200)
            ->assertJsonPath('total', 2)
            ->assertJsonPath('eligible_now', 1)
            ->assertJsonPath('employees.0.id', $eligible->id)
            ->assertJsonPath('employees.1.id', $near->id)
            ->assertJsonMissingPath('employees.2.id');
    }

    public function test_upcoming_respects_custom_window()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        $near = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'date_of_birth' => now()->subYears(59)->toDateString(),
            'status' => 'active',
        ]);

        $response = $this->getJsonAuth('/api/rh/retirement/upcoming?within_years=2');

        $response->assertStatus(200)
            ->assertJsonPath('within_years', 2)
            ->assertJsonPath('total', 1)
            ->assertJsonPath('employees.0.id', $near->id);
    }
}
