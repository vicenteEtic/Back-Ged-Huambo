<?php

namespace Tests\Feature\RH\Leave;

use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\Holiday;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveRequest;
use App\Models\RH\Leave\LeaveType;
use App\Models\RH\Position\Position;
use Carbon\Carbon;
use Tests\Feature\RH\RhTestCase;

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

        $response = $this->getJsonAuth('/api/rh/leaves/leave-requests/'.$leave->id);
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

        $response = $this->putJsonAuth('/api/rh/leaves/leave-requests/'.$leave->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/leaves/leave-requests/'.$leave->id);
        $response->assertStatus(204);
    }

    public function test_can_get_balance()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/leaves/leave-requests/'.$leave->id.'/balance');
        $response->assertStatus(200);
    }

    public function test_can_calculate_return_date_by_days()
    {
        $start = now()->next(Carbon::MONDAY);

        Holiday::create([
            'name' => 'Feriado Teste',
            'date' => $start->copy()->addDays(2)->format('Y-m-d'),
            'recurrent' => false,
            'is_active' => true,
        ]);

        $response = $this->getJsonAuth(
            '/api/rh/leaves/leave-requests/calculate-return?start_date='.$start->format('Y-m-d').'&days=10'
        );

        $response->assertStatus(200);

        $this->assertEquals($start->format('Y-m-d'), $response->json('start_date'));
        $this->assertEquals(10, $response->json('days'));
        $this->assertEquals($start->copy()->addDays(14)->format('Y-m-d'), $response->json('end_date'));
        $this->assertEquals($start->copy()->addDays(15)->format('Y-m-d'), $response->json('return_date'));
        $this->assertEquals(1, $response->json('holidays_count'));
        $this->assertEquals($start->copy()->addDays(2)->format('Y-m-d'), $response->json('holidays.0.date'));
        $this->assertEquals('Feriado Teste', $response->json('holidays.0.name'));
        $this->assertEquals(15, $response->json('calendar_days'));
    }

    public function test_calculate_return_validates_days()
    {
        $start = now()->next(Carbon::MONDAY);

        $response = $this->getJsonAuth(
            '/api/rh/leaves/leave-requests/calculate-return?start_date='.$start->format('Y-m-d').'&days=0'
        );

        $response->assertStatus(422);
    }

    public function test_submit_computes_end_date_from_days()
    {
        $start = now()->next(Carbon::MONDAY);

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $start->format('Y-m-d'),
            'days' => 5,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('total_days', 5);

        $this->assertEquals(
            $start->copy()->addDays(4)->startOfDay()->timestamp,
            Carbon::parse($response->json('end_date'))->timestamp
        );
        $this->assertEquals(
            $start->copy()->addDays(7)->startOfDay()->timestamp,
            Carbon::parse($response->json('return_date'))->timestamp
        );
    }

    public function test_submit_with_days_skips_weekend()
    {
        $start = now()->next(Carbon::MONDAY);

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $start->format('Y-m-d'),
            'days' => 10,
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('total_days', 10);

        $this->assertEquals(
            $start->copy()->addDays(11)->startOfDay()->timestamp,
            Carbon::parse($response->json('end_date'))->timestamp
        );
        $this->assertEquals(
            $start->copy()->addDays(14)->startOfDay()->timestamp,
            Carbon::parse($response->json('return_date'))->timestamp
        );
    }

    public function test_submit_excludes_holidays_from_business_days()
    {
        $start = now()->next(Carbon::MONDAY);
        $end = $start->copy()->addDays(4);

        Holiday::create([
            'name' => 'Feriado Teste',
            'date' => $start->copy()->addDays(2)->format('Y-m-d'),
            'recurrent' => false,
            'is_active' => true,
        ]);

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('total_days', 4);
    }

    public function test_submit_computes_return_date_skipping_weekends_and_holidays()
    {
        $holiday = now()->addWeeks(3)->next(Carbon::MONDAY);
        Holiday::create([
            'name' => 'Feriado Teste',
            'date' => $holiday->format('Y-m-d'),
            'recurrent' => false,
            'is_active' => true,
        ]);

        $end = $holiday->copy()->subDays(3);

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'start_date' => $end->copy()->subDays(2)->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
        $this->assertEquals(
            $holiday->copy()->addDay()->startOfDay()->timestamp,
            Carbon::parse($response->json('return_date'))->timestamp
        );
    }

    public function test_update_recalculates_total_days()
    {
        $leave = LeaveRequest::factory()->create([
            'employee_id' => $this->employee->id,
            'leave_type_id' => $this->leaveType->id,
            'leave_plan_id' => $this->leavePlan->id,
            'start_date' => now()->format('Y-m-d'),
            'end_date' => now()->format('Y-m-d'),
            'total_days' => 1,
            'status' => 'pending',
        ]);

        $start = now()->addDays(30)->next(Carbon::MONDAY);
        $end = $start->copy()->addDays(2);

        $response = $this->putJsonAuth('/api/rh/leaves/leave-requests/'.$leave->id, [
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $end->format('Y-m-d'),
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('total_days', 3);
    }

    public function test_cannot_submit_annual_leave_before_six_months_of_service()
    {
        $annual = LeaveType::factory()->create([
            'code' => 'ANNUAL',
            'service_years_based' => true,
        ]);

        $employee = Employee::factory()->create([
            'department_id' => $this->employee->department_id,
            'position_id' => $this->employee->position_id,
            'user_id' => $this->user->id,
            'hire_date' => now()->subMonths(2)->format('Y-m-d'),
            'effective_date' => null,
        ]);

        $start = now()->addDays(30);

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $start->copy()->addDays(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Férias do ano de admissão só podem ser gozadas após 6 meses de trabalho efectivo (art. 77.º n.º 3 da Lei 26/22).');
    }

    public function test_can_submit_annual_leave_after_six_months_of_service()
    {
        $annual = LeaveType::factory()->create([
            'code' => 'ANNUAL',
            'service_years_based' => true,
        ]);

        $employee = Employee::factory()->create([
            'department_id' => $this->employee->department_id,
            'position_id' => $this->employee->position_id,
            'user_id' => $this->user->id,
            'hire_date' => now()->subMonths(8)->format('Y-m-d'),
            'effective_date' => null,
        ]);

        $start = now()->addDays(30);

        $response = $this->postJsonAuth('/api/rh/leaves/leave-requests', [
            'employee_id' => $employee->id,
            'leave_type_id' => $annual->id,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => $start->copy()->addDays(2)->format('Y-m-d'),
        ]);

        $response->assertStatus(201);
    }
}
