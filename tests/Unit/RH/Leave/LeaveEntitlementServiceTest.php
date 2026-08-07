<?php

namespace Tests\Unit\RH\Leave;

use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Position\Position;
use App\Services\RH\Leave\LeaveEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeaveEntitlementServiceTest extends TestCase
{
    use RefreshDatabase;

    protected LeaveEntitlementService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new LeaveEntitlementService();
    }

    private function annualLeaveType(): LeaveType
    {
        return LeaveType::factory()->create([
            'code' => 'ANNUAL',
            'default_days' => 22,
            'service_years_based' => true,
        ]);
    }

    private function employeeWithHireDate(string $date): Employee
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);

        return Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
            'hire_date' => $date,
            'effective_date' => null,
            'institution_entry_date' => null,
        ]);
    }

    public function test_years_of_service_uses_effective_date()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(8)->format('Y-m-d'));
        $employee->effective_date = now()->subYears(3)->format('Y-m-d');

        $this->assertSame(3.0, $this->service->yearsOfService($employee));
    }

    public function test_one_to_five_years_gets_22_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(3)->format('Y-m-d'));
        $this->assertSame(22.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_six_to_ten_years_gets_23_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(8)->format('Y-m-d'));
        $this->assertSame(23.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_eleven_to_fifteen_years_gets_24_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(13)->format('Y-m-d'));
        $this->assertSame(24.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_sixteen_to_twenty_years_gets_25_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(18)->format('Y-m-d'));
        $this->assertSame(25.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_twenty_one_to_twenty_five_years_gets_26_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(23)->format('Y-m-d'));
        $this->assertSame(26.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_more_than_twenty_five_years_gets_30_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(30)->format('Y-m-d'));
        $this->assertSame(30.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_less_than_one_year_is_proportional()
    {
        $employee = $this->employeeWithHireDate(now()->subMonths(6)->format('Y-m-d'));

        $this->assertSame(11.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }

    public function test_non_service_based_type_uses_default_days()
    {
        $employee = $this->employeeWithHireDate(now()->subYears(30)->format('Y-m-d'));
        $type = LeaveType::factory()->create(['service_years_based' => false, 'default_days' => 7]);

        $this->assertSame(7.0, $this->service->entitledDays($employee, $type));
    }

    public function test_employee_without_dates_falls_back_to_default()
    {
        $employee = $this->employeeWithHireDate('2020-01-01');
        $employee->hire_date = null;
        $employee->effective_date = null;
        $employee->institution_entry_date = null;

        $this->assertSame(22.0, $this->service->entitledDays($employee, $this->annualLeaveType()));
    }
}
