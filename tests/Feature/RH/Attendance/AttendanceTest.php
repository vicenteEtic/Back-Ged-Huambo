<?php

namespace Tests\Feature\RH\Attendance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\Shift;
use App\Models\RH\Attendance\ShiftAssignment;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class AttendanceTest extends RhTestCase
{
    protected Employee $employee;
    protected Shift $shift;

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

        $this->shift = Shift::factory()->create();
    }

    public function test_can_list_records()
    {
        Attendance::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/records');
        $response->assertStatus(200);
    }

    public function test_can_create_record()
    {
        $data = Attendance::factory()->make([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/attendance/records', $data);
        $response->assertStatus(201);
    }

    public function test_can_show_record()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/records/' . $attendance->id);
        $response->assertStatus(200);
    }

    public function test_can_update_record()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ]);

        $data = Attendance::factory()->make([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/attendance/records/' . $attendance->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy_record()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/attendance/records/' . $attendance->id);
        $response->assertStatus(204);
    }

    public function test_can_check_in()
    {
        ShiftAssignment::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ]);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => '08:00:00',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/check-in', $data);
        $response->assertStatus(200);
    }

    public function test_can_check_out()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => '08:00',
            'check_out' => null,
            'status' => 'present',
        ]);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => $attendance->date->format('Y-m-d'),
            'check_out' => '17:00:00',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/check-out', $data);
        $response->assertStatus(200);
    }

    public function test_can_register_absence()
    {
        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'absence_type' => 'justified',
            'absence_reason' => 'Doença',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/absence', $data);
        $response->assertStatus(200);
    }

    public function test_can_import_biometric()
    {
        $response = $this->postJsonAuth('/api/rh/attendance/import-biometric', [
            'file' => 'test.csv',
        ]);
        $response->assertStatus(422);
    }

    public function test_can_get_monthly_report()
    {
        Attendance::factory()->count(5)->create([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/reports/' . $this->employee->id);
        $response->assertStatus(200);
    }

    public function test_can_list_shifts()
    {
        $response = $this->getJsonAuth('/api/rh/attendance/shifts');
        $response->assertStatus(200);
    }

    public function test_can_create_shift()
    {
        $data = Shift::factory()->make()->toArray();

        $response = $this->postJsonAuth('/api/rh/attendance/shifts', $data);
        $response->assertStatus(201);
    }

    public function test_can_list_assignments()
    {
        $response = $this->getJsonAuth('/api/rh/attendance/assignments');
        $response->assertStatus(200);
    }

    public function test_can_create_assignment()
    {
        $data = ShiftAssignment::factory()->make([
            'employee_id' => $this->employee->id,
            'shift_id' => $this->shift->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/attendance/assignments', $data);
        $response->assertStatus(201);
    }
}