<?php

namespace Tests\Feature\RH\Leave;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class LeaveRequestTest extends RhTestCase
{
    protected Employee $employee;
    protected LeaveType $leaveType;
    protected LeavePlan $leavePlan;

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

        $this->leaveType = LeaveType::factory()->create();
        $this->leavePlan = LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
            'year' => now()->year,
        ]);
    }

    public function test_can_list()
    {
        LeaveRequest::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/leave-requests');
        $response->assertStatus(200);
    }

    public function test_can_create()
    {
        $data = LeaveRequest::factory()->make([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', $data);
        $response->assertStatus(201);
    }

    public function test_can_show()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/leave-requests/' . $leave->id);
        $response->assertStatus(200);
    }

    public function test_can_update()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $data = LeaveRequest::factory()->make([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/leaves/leave-requests/' . $leave->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/leaves/leave-requests/' . $leave->id);
        $response->assertStatus(204);
    }

    public function test_can_get_balance()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/leave-requests/' . $leave->id . '/balance');
        $response->assertStatus(200);
    }
}
