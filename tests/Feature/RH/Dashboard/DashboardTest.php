<?php

namespace Tests\Feature\RH\Dashboard;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\Shift;
use App\Models\RH\EmployeeDocument\EmployeeDocument;

class DashboardTest extends RhTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        Employee::factory()->count(5)->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
    }

    public function test_can_get_overview()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/overview');
        $response->assertStatus(200);
    }

    public function test_can_get_monthly_birthdays()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/monthly-birthdays');
        $response->assertStatus(200);
    }

    public function test_can_get_leave_summary()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/leave-summary');
        $response->assertStatus(200);
    }

    public function test_can_get_attendance_summary()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/attendance-summary');
        $response->assertStatus(200);
    }

    public function test_can_get_document_expiry_alert()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/document-expiry-alert');
        $response->assertStatus(200);
    }

    public function test_can_get_turnover()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/turnover');
        $response->assertStatus(200);
    }

    public function test_can_get_salary_evolution()
    {
        $response = $this->getJsonAuth('/api/rh/dashboard/salary-evolution');
        $response->assertStatus(200);
    }
}
