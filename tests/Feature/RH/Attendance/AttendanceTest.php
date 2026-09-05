<?php

namespace Tests\Feature\RH\Attendance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Attendance\AbsenceType;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;
use App\Models\User;

class AttendanceTest extends RhTestCase
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

    public function test_can_list_records()
    {
        Attendance::factory()->count(3)->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/records');
        $response->assertStatus(200);
    }

    public function test_can_create_record()
    {
        $data = Attendance::factory()->make([
            'employee_id' => $this->employee->id,
        ])->toArray();

        $response = $this->postJsonAuth('/api/rh/attendance/records', $data);
        $response->assertStatus(201);
    }

    public function test_can_show_record()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/records/' . $attendance->id);
        $response->assertStatus(200);
    }

    public function test_can_update_record()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $data = Attendance::factory()->make([
            'employee_id' => $this->employee->id,
        ])->toArray();

        $response = $this->putJsonAuth('/api/rh/attendance/records/' . $attendance->id, $data);
        $response->assertStatus(200);
    }

    public function test_can_destroy_record()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
        ]);

        $response = $this->deleteJsonAuth('/api/rh/attendance/records/' . $attendance->id);
        $response->assertStatus(204);
    }

    public function test_can_check_in()
    {
        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => '08:00:00',
            'notes' => 'Chegou cedo para reunião',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/check-in', $data);
        $response->assertStatus(200)
            ->assertJsonPath('notes', 'Chegou cedo para reunião')
            ->assertJsonPath('status', 'present')
            ->assertJsonPath('late_minutes', 0);

        $this->assertDatabaseHas('attendance', [
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'notes' => 'Chegou cedo para reunião',
        ]);
    }

    public function test_can_check_out()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => '08:00',
            'check_out' => null,
            'notes' => 'Entrada registada',
            'status' => 'present',
        ]);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => $attendance->date->format('Y-m-d'),
            'check_out' => '17:00:00',
            'notes' => 'Saída registada',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/check-out', $data);
        $response->assertStatus(200)
            ->assertJsonPath('notes', 'Saída registada')
            ->assertJsonPath('overtime_minutes', 0);

        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'notes' => 'Saída registada',
        ]);
    }

    public function test_check_out_preserves_existing_note_when_not_sent()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'check_in' => '08:00',
            'check_out' => null,
            'notes' => 'Observação na entrada',
            'status' => 'present',
        ]);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => $attendance->date->format('Y-m-d'),
            'check_out' => '17:00:00',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/check-out', $data);
        $response->assertStatus(200)
            ->assertJsonPath('notes', 'Observação na entrada');

        $this->assertDatabaseHas('attendance', [
            'id' => $attendance->id,
            'notes' => 'Observação na entrada',
        ]);
    }

    public function test_can_register_absence()
    {
        AbsenceType::factory()->create(['code' => 'doenca']);

        $data = [
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'absence_type' => 'doenca',
            'reason' => 'Doença',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/absences/justifications', $data);
        $response->assertStatus(201);
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
        $dates = collect(range(1, now()->daysInMonth))
            ->map(fn(int $day) => now()->startOfMonth()->addDays($day - 1)->format('Y-m-d'))
            ->reject(fn(string $date) => $date === now()->format('Y-m-d'))
            ->take(5)
            ->values();

        foreach ($dates as $date) {
            Attendance::factory()->create([
                'employee_id' => $this->employee->id,
                'date' => $date,
            ]);
        }

        Attendance::factory()->create([
            'employee_id' => $this->employee->id,
            'date' => now()->format('Y-m-d'),
            'notes' => 'Observação nos relatórios',
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/reports/' . $this->employee->id);
        $response->assertStatus(200)
            ->assertJsonCount(6, 'records')
            ->assertJsonFragment(['notes' => 'Observação nos relatórios']);
    }
}