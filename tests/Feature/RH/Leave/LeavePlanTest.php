<?php

namespace Tests\Feature\RH\Leave;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class LeavePlanTest extends RhTestCase
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
        ]);
    }

    public function test_can_list()
    {
        LeavePlan::factory()->count(3)->create();

        $response = $this->getJsonAuth('/api/rh/leaves/plans');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $data = LeavePlan::factory()->make([
            'employee_id' => $this->employee->id,
            'created_by' => $this->user->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/leaves/plans', $data);
        $response->assertStatus(201);
    }

    public function test_can_show()
    {
        $plan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/plans/' . $plan->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $plan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $data = LeavePlan::factory()->make([
            'employee_id' => $this->employee->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/leaves/plans/' . $plan->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $plan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/leaves/plans/' . $plan->id);
        $response->assertStatus(204);
    }

    public function test_can_sync_balance()
    {
        $plan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->postJsonAuth('/api/rh/leaves/plans/' . $plan->id . '/sync-balance');
        $response->assertStatus(200);
    }

    public function test_can_get_calendar()
    {
        $response = $this->getJsonAuth('/api/rh/leaves/calendar');
        $response->assertStatus(200);
    }

    public function test_can_get_calendar_with_year_filter()
    {
        $response = $this->getJsonAuth('/api/rh/leaves/calendar?year=' . now()->year);
        $response->assertStatus(200);
    }
}
