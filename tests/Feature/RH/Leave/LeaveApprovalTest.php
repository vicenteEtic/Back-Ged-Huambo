<?php

namespace Tests\Feature\RH\Leave;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Leave\LeaveApproval;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Employee\Employee;
use App\Models\User;

class LeaveApprovalTest extends RhTestCase
{
    public function test_can_list_pending()
    {
        $leaveType = LeaveType::factory()->create();
        $employee = Employee::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);
        LeaveApproval::factory()->create([
            'leave_request_id' => $leaveRequest->id,
            'approver_id' => $this->user->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/approvals/pending');
        $response->assertStatus(200);
    }

    public function test_can_approve()
    {
        $leaveType = LeaveType::factory()->create();
        $employee = Employee::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        $response = $this->postJsonAuth('/api/rh/leaves/approvals/' . $leaveRequest->id . '/approve', [
            'comment' => 'Aprovado',
        ]);
        $response->assertStatus(200);
    }

    public function test_can_reject()
    {
        $leaveType = LeaveType::factory()->create();
        $employee = Employee::factory()->create();
        $leaveRequest = LeaveRequest::factory()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
        ]);

        $response = $this->postJsonAuth('/api/rh/leaves/approvals/' . $leaveRequest->id . '/reject', [
            'comment' => 'Rejeitado',
        ]);
        $response->assertStatus(200);
    }
}
