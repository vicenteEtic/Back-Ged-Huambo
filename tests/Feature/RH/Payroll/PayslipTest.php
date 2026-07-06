<?php

namespace Tests\Feature\RH\Payroll;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Payroll\Payslip;
use App\Models\RH\Payroll\PayrollPeriod;
use App\Models\RH\Payroll\PayrollItem;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class PayslipTest extends RhTestCase
{
    protected Employee $employee;
    protected PayrollPeriod $period;

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

        $this->period = PayrollPeriod::factory()->create();
    }

    public function test_can_list()
    {
        Payslip::factory()->count(3)->create();

        $response = $this->getJsonAuth('/api/rh/payslips');
        $response->assertStatus(200);
    }

    public function test_can_show()
    {
        $payslip = Payslip::factory()->create([
            'employee_id' => $this->employee->id,
            'payroll_period_id' => $this->period->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/payslips/' . $payslip->id);
        $response->assertStatus(200);
    }

    public function test_can_list_by_employee()
    {
        Payslip::factory()->count(2)->create();

        $response = $this->getJsonAuth('/api/rh/payslips/by-employee/' . $this->employee->id);
        $response->assertStatus(200);
    }

    public function test_can_generate()
    {
        PayrollItem::factory()->count(3)->create([
            'payroll_period_id' => $this->period->id,
        ]);

        $response = $this->postJsonAuth('/api/rh/payslips/generate/' . $this->period->id, [
            'employee_ids' => [$this->employee->id],
        ]);
        $response->assertStatus(200);
    }

    public function test_generate_returns_error_without_employees()
    {
        $response = $this->postJsonAuth('/api/rh/payslips/generate/' . $this->period->id);
        $response->assertStatus(422);
    }
}
