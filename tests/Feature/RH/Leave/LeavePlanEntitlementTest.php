<?php

namespace Tests\Feature\RH\Leave;

use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Position\Position;
use Tests\Feature\RH\RhTestCase;

class LeavePlanEntitlementTest extends RhTestCase
{
    protected Department $department;

    protected Position $position;

    protected Employee $employee;

    protected LeaveType $annualType;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create(['department_id' => $this->department->id]);
        $this->employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'user_id' => $this->user->id,
            'hire_date' => now()->subYears(8)->format('Y-m-d'),
            'effective_date' => null,
        ]);

        $this->annualType = LeaveType::factory()->create([
            'code' => 'ANNUAL',
            'name' => 'Férias Anuais',
            'default_days' => 22,
            'service_years_based' => true,
        ]);
    }

    public function test_balance_returns_entitlement_based_on_service_years()
    {
        $response = $this->getJsonAuth('/api/rh/leaves/leave-requests/'.$this->employee->id.'/balance?leave_type_id='.$this->annualType->id);
        $response->assertStatus(200)
            ->assertJsonPath('total_days_entitled', '22.0')
            ->assertJsonPath('years_of_service', 8);
    }

    public function test_submit_annual_leave_creates_plan_with_entitlement()
    {
        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->annualType->id,
            'start_date' => now()->addMonth()->format('Y-m-d'),
            'end_date' => now()->addMonth()->addDays(4)->format('Y-m-d'),
        ]);

        $response->assertStatus(201);

        $plan = LeavePlan::where('employee_id', $this->employee->id)
            ->where('year', now()->year)
            ->where('leave_type_id', $this->annualType->id)
            ->first();

        $this->assertNotNull($plan);
        $this->assertSame(22.0, (float) $plan->total_days_entitled);
    }

    public function test_manual_plan_creation_auto_computes_entitlement()
    {
        $response = $this->postJsonAuth('/api/rh/leaves/plans', [
            'employee_id' => $this->employee->id,
            'year' => now()->year,
            'leave_type_id' => $this->annualType->id,
        ]);

        $response->assertStatus(201);

        $plan = LeavePlan::where('employee_id', $this->employee->id)
            ->where('year', now()->year)
            ->where('leave_type_id', $this->annualType->id)
            ->first();

        $this->assertNotNull($plan);
        $this->assertSame(22.0, (float) $plan->total_days_entitled);
    }

    public function test_manual_plan_creation_honours_explicit_total()
    {
        $response = $this->postJsonAuth('/api/rh/leaves/plans', [
            'employee_id' => $this->employee->id,
            'year' => now()->year,
            'leave_type_id' => $this->annualType->id,
            'total_days_entitled' => 30,
        ]);

        $response->assertStatus(201);

        $plan = LeavePlan::where('employee_id', $this->employee->id)
            ->where('year', now()->year)
            ->where('leave_type_id', $this->annualType->id)
            ->first();

        $this->assertNotNull($plan);
        $this->assertSame(30.0, (float) $plan->total_days_entitled);
    }

    public function test_annual_entitlement_returns_days_by_service_time()
    {
        $response = $this->getJsonAuth('/api/rh/leaves/annual-entitlement/'.$this->employee->id);
        $response->assertStatus(200)
            ->assertJsonPath('is_annual', true)
            ->assertJsonPath('employee_id', $this->employee->id)
            ->assertJsonPath('years_of_service', 8)
            ->assertJsonPath('bracket', '1 a 9 anos de serviço')
            ->assertJsonPath('entitled_days', 22)
            ->assertJsonPath('leave_type_id', $this->annualType->id);
    }

    public function test_annual_entitlement_admission_year_two_days_per_month_min_six()
    {
        $employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'user_id' => $this->user->id,
            'hire_date' => now()->subMonths(6)->format('Y-m-d'),
            'effective_date' => null,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/annual-entitlement/'.$employee->id);
        $response->assertStatus(200)
            ->assertJsonPath('is_annual', true)
            ->assertJsonPath('bracket', 'Ano de admissão (2 dias por mês completo, mínimo 6 dias)')
            ->assertJsonPath('entitled_days', 12)
            ->assertJsonPath('proportional_days', 12);
    }

    public function test_annual_entitlement_admission_year_uses_minimum_six_days()
    {
        $employee = Employee::factory()->create([
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
            'user_id' => $this->user->id,
            'hire_date' => now()->subDays(25)->format('Y-m-d'),
            'effective_date' => null,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/annual-entitlement/'.$employee->id);
        $response->assertStatus(200)
            ->assertJsonPath('is_annual', true)
            ->assertJsonPath('bracket', 'Ano de admissão (2 dias por mês completo, mínimo 6 dias)')
            ->assertJsonPath('entitled_days', 6)
            ->assertJsonPath('proportional_days', 6);
    }

    public function test_annual_entitlement_returns_default_days_for_non_service_based_type()
    {
        $medical = LeaveType::factory()->create([
            'name' => 'Licença Médica',
            'code' => 'MEDICAL',
            'default_days' => 30,
            'service_years_based' => false,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/annual-entitlement/'.$this->employee->id.'?leave_type_id='.$medical->id);
        $response->assertStatus(200)
            ->assertJsonPath('is_annual', false)
            ->assertJsonPath('leave_type_name', 'Licença Médica')
            ->assertJsonPath('default_days', 30)
            ->assertJsonPath('entitled_days', 30);
    }

    public function test_annual_entitlement_returns_404_for_missing_employee()
    {
        $response = $this->getJsonAuth('/api/rh/leaves/annual-entitlement/99999');
        $response->assertStatus(404)
            ->assertJsonPath('error', 'Recurso não encontrado.');
    }
}
