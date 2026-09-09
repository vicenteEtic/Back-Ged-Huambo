<?php

namespace Tests\Feature\RH\Leave;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Leave\LeaveRequest;
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

    public function test_leave_type_is_required_when_creating_a_plan()
    {
        $data = LeavePlan::factory()->make([
            'employee_id' => $this->employee->id,
            'leave_type_id' => null,
        ])->toArray();

        $this->postJsonAuth('/api/rh/leaves/plans', $data)
            ->assertStatus(422)
            ->assertJsonValidationErrors('leave_type_id');
    }

    public function test_store_syncs_plan_balance_and_ignores_other_leave_types()
    {
        $type = LeaveType::factory()->create();
        $otherType = LeaveType::factory()->create();
        $plan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $type->id,
            'year' => now()->year,
        ]);

        LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $type->id,
            'leave_plan_id' => $plan->id,
            'status' => 'approved',
            'total_days' => 3,
        ]);
        LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $otherType->id,
            'leave_plan_id' => $plan->id,
            'status' => 'approved',
            'total_days' => 7,
        ]);

        $response = $this->postJsonAuth('/api/rh/leaves/plans', [
            'employee_id' => $this->employee->id,
            'year' => now()->year,
            'leave_type_id' => $type->id,
            'total_days_entitled' => 22,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('days_used', '3.0');
    }

    public function test_store_restores_a_soft_deleted_plan_instead_of_creating_a_duplicate()
    {
        $type = LeaveType::factory()->create();
        $plan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
            'year' => 2025,
            'leave_type_id' => $type->id,
        ]);

        $plan->delete();

        $this->postJsonAuth('/api/rh/leaves/plans', [
            'employee_id' => $this->employee->id,
            'year' => 2025,
            'leave_type_id' => $type->id,
            'expected_month' => 6,
            'total_days_entitled' => 22,
        ])->assertStatus(201);

        $this->assertDatabaseHas('leave_plans', [
            'id' => $plan->id,
            'deleted_at' => null,
            'expected_month' => 6,
        ]);
    }

    public function test_calendar_can_filter_by_leave_type()
    {
        $type = LeaveType::factory()->create();
        $otherType = LeaveType::factory()->create();
        LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $type->id,
            'start_date' => now()->startOfYear()->format('Y-m-d'),
            'end_date' => now()->startOfYear()->format('Y-m-d'),
        ]);
        LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $otherType->id,
            'start_date' => now()->startOfYear()->addDay()->format('Y-m-d'),
            'end_date' => now()->startOfYear()->addDay()->format('Y-m-d'),
        ]);

        $this->getJsonAuth('/api/rh/leaves/calendar?year='.now()->year.'&leave_type_id='.$type->id)
            ->assertStatus(200)
            ->assertJsonCount(1);
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
