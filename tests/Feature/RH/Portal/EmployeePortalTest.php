<?php

namespace Tests\Feature\RH\Portal;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\RH\Payroll\Payslip;
use App\Models\RH\Payroll\PayrollPeriod;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Leave\LeaveRequest;

class EmployeePortalTest extends RhTestCase
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

    public function test_can_view_profile()
    {
        $response = $this->getJsonAuth('/api/rh/portal/profile');
        $response->assertStatus(200);
    }

    public function test_can_view_leave_balance()
    {
        LeavePlan::factory()->create([
            'employee_id' => $this->employee->id,
            'year' => now()->year,
        ]);

        $response = $this->getJsonAuth('/api/rh/portal/leave-balance');
        $response->assertStatus(200);
    }

    public function test_can_view_salary_history()
    {
        $periods = PayrollPeriod::factory()->count(3)->create();
        $periods->each(function ($period) {
            Payslip::factory()->create([
                'employee_id' => $this->employee->id,
                'payroll_period_id' => $period->id,
            ]);
        });

        $response = $this->getJsonAuth('/api/rh/portal/salary-history');
        $response->assertStatus(200);
    }

    public function test_can_view_career()
    {
        $response = $this->getJsonAuth('/api/rh/portal/career');
        $response->assertStatus(200);
    }

    public function test_can_view_benefits()
    {
        $response = $this->getJsonAuth('/api/rh/portal/benefits');
        $response->assertStatus(200);
    }

    public function test_can_download_payslip()
    {
        $period = PayrollPeriod::factory()->create();
        $payslip = Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $period->id,
        ]);

        $response = $this->postJsonAuth('/api/rh/portal/payslip/' . $payslip->id . '/download');
        $response->assertStatus(200);
    }

    public function test_cannot_access_other_employee_payslip()
    {
        $otherEmployee = Employee::factory()->create();
        $period = PayrollPeriod::factory()->create();
        $payslip = Payslip::factory()->create([
            'employee_id' => $otherEmployee->id,
            'payroll_period_id' => $period->id,
        ]);

        $response = $this->postJsonAuth('/api/rh/portal/payslip/' . $payslip->id . '/download');
        $response->assertStatus(404);
    }
}
