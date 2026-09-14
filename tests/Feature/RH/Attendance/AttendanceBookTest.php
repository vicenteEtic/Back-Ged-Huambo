<?php

namespace Tests\Feature\RH\Attendance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\AttendanceBookConfig;
use App\Models\RH\Department\Department;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Position\Position;

class AttendanceBookTest extends RhTestCase
{
    protected Department $department;
    protected Position $position;

    protected function setUp(): void
    {
        parent::setUp();

        $this->department = Department::factory()->create();
        $this->position = Position::factory()->create(['department_id' => $this->department->id]);
    }

    private function makeEmployee(array $overrides = []): Employee
    {
        return Employee::factory()->create(array_merge([
            'user_id' => $this->user->id,
            'department_id' => $this->department->id,
            'position_id' => $this->position->id,
        ], $overrides));
    }

    private function registeredFor(string $date): Employee
    {
        $employee = $this->makeEmployee();
        Attendance::factory()->create([
            'employee_id' => $employee->id,
            'date' => $date,
        ]);

        return $employee;
    }

    /** Listagem inteligente de funcionários disponíveis */
    public function test_available_employees_excludes_those_already_registered()
    {
        $date = now()->toDateString();

        $registered = $this->registeredFor($date);
        $available = $this->makeEmployee();

        $response = $this->getJsonAuth('/api/rh/attendance/employees?date='.$date);

        $response->assertStatus(200)
            ->assertJsonPath('date', $date)
            ->assertJsonFragment(['id' => $available->id])
            ->assertJsonMissing(['id' => $registered->id]);
    }

    public function test_available_employees_defaults_to_today()
    {
        $employee = $this->makeEmployee();

        $response = $this->getJsonAuth('/api/rh/attendance/employees');

        $response->assertStatus(200)
            ->assertJsonPath('date', now()->toDateString())
            ->assertJsonFragment(['id' => $employee->id]);
    }

    public function test_available_employees_filters_by_departments()
    {
        $date = now()->toDateString();

        $otherDepartment = Department::factory()->create();
        $otherPosition = Position::factory()->create(['department_id' => $otherDepartment->id]);

        $inTarget = $this->makeEmployee();
        $other = $this->makeEmployee([
            'department_id' => $otherDepartment->id,
            'position_id' => $otherPosition->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/employees?date='.$date.'&department_ids[]='.$this->department->id);

        $response->assertStatus(200)
            ->assertJsonCount(1, 'employees')
            ->assertJsonFragment(['id' => $inTarget->id])
            ->assertJsonMissing(['id' => $other->id]);
    }

    public function test_available_employees_respects_configured_book_departments()
    {
        $date = now()->toDateString();

        $otherDepartment = Department::factory()->create();
        $otherPosition = Position::factory()->create(['department_id' => $otherDepartment->id]);

        $inBook = $this->makeEmployee();
        $outside = $this->makeEmployee([
            'department_id' => $otherDepartment->id,
            'position_id' => $otherPosition->id,
        ]);

        AttendanceBookConfig::create([
            'attendance_book_departments' => [$this->department->id],
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/employees?date='.$date);

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $inBook->id])
            ->assertJsonMissing(['id' => $outside->id]);
    }

    public function test_available_employees_excludes_legacy_exempt_department()
    {
        $date = now()->toDateString();

        $exemptDepartment = Department::factory()->create(['code' => 'GEPE', 'name' => 'GEPE']);
        $exemptPosition = Position::factory()->create(['department_id' => $exemptDepartment->id]);

        $regular = $this->makeEmployee();
        $exempt = $this->makeEmployee([
            'department_id' => $exemptDepartment->id,
            'position_id' => $exemptPosition->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/employees?date='.$date);

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $regular->id])
            ->assertJsonMissing(['id' => $exempt->id]);
    }

    /** Saída individual */
    public function test_exit_marks_check_out_and_hours()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->makeEmployee()->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => null,
            'status' => 'present',
        ]);

        $response = $this->patchJson('/api/rh/attendance/records/'.$attendance->id.'/exit', [
            'check_out' => '15:30',
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonPath('check_out', '15:30:00')
            ->assertJsonPath('hours_worked', '7.50');
    }

    public function test_exit_accepts_expected_check_out_alias()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->makeEmployee()->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => null,
            'status' => 'present',
        ]);

        $response = $this->patchJson('/api/rh/attendance/records/'.$attendance->id.'/exit', [
            'expected_check_out' => '15:30',
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonPath('check_out', '15:30:00');
    }

    public function test_exit_returns_404_when_record_missing()
    {
        $response = $this->patchJson('/api/rh/attendance/records/999999/exit', [
            'check_out' => '15:30',
        ], $this->headers);

        $response->assertStatus(404);
    }

    public function test_exit_rejects_when_already_has_check_out()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->makeEmployee()->id,
            'date' => now()->toDateString(),
            'check_in' => '08:00:00',
            'check_out' => '15:00:00',
            'status' => 'present',
        ]);

        $response = $this->patchJson('/api/rh/attendance/records/'.$attendance->id.'/exit', [
            'check_out' => '16:00',
        ], $this->headers);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Este registo já possui saída registada.');
    }

    public function test_exit_rejects_when_no_check_in()
    {
        $attendance = Attendance::factory()->create([
            'employee_id' => $this->makeEmployee()->id,
            'date' => now()->toDateString(),
            'check_in' => null,
            'check_out' => null,
            'status' => 'present',
        ]);

        $response = $this->patchJson('/api/rh/attendance/records/'.$attendance->id.'/exit', [
            'check_out' => '15:30',
        ], $this->headers);

        $response->assertStatus(422)
            ->assertJsonPath('error', 'Registo sem entrada: marca primeiro a entrada.');
    }

    /** Saída em lote */
    public function test_bulk_exit_updates_multiple_records()
    {
        $date = now()->toDateString();

        $first = $this->makeEmployee();
        $second = $this->makeEmployee();

        $a1 = Attendance::factory()->create([
            'employee_id' => $first->id,
            'date' => $date,
            'check_in' => '08:00:00',
            'check_out' => null,
        ]);
        $a2 = Attendance::factory()->create([
            'employee_id' => $second->id,
            'date' => $date,
            'check_in' => '08:00:00',
            'check_out' => null,
        ]);

        $response = $this->patchJson('/api/rh/attendance/records/bulk-exit', [
            'date' => $date,
            'records' => [
                ['employee_id' => $first->id, 'expected_check_out' => '15:30'],
                ['employee_id' => $second->id, 'expected_check_out' => '15:31'],
            ],
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonPath('updated', 2)
            ->assertJsonPath('failed', 0);

        $this->assertDatabaseHas('attendance', ['id' => $a1->id, 'check_out' => '15:30:00']);
        $this->assertDatabaseHas('attendance', ['id' => $a2->id, 'check_out' => '15:31:00']);
    }

    public function test_bulk_exit_validates_each_record_individually()
    {
        $date = now()->toDateString();

        $withRecord = $this->makeEmployee();
        $withoutRecord = $this->makeEmployee();

        Attendance::factory()->create([
            'employee_id' => $withRecord->id,
            'date' => $date,
            'check_in' => '08:00:00',
            'check_out' => null,
        ]);

        $response = $this->patchJson('/api/rh/attendance/records/bulk-exit', [
            'date' => $date,
            'records' => [
                ['employee_id' => $withRecord->id, 'check_out' => '15:30'],
                ['employee_id' => $withoutRecord->id, 'check_out' => '15:31'],
            ],
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonPath('updated', 1)
            ->assertJsonPath('failed', 1)
            ->assertJsonFragment([
                'employee_id' => $withoutRecord->id,
                'success' => false,
                'error' => 'Sem registo de entrada nesta data.',
            ]);
    }

    /** Livro de ponto diário */
    public function test_daily_book_includes_employees_without_record_as_absent()
    {
        $date = now()->toDateString();

        $withRecord = $this->makeEmployee();
        $withoutRecord = $this->makeEmployee();

        Attendance::factory()->create([
            'employee_id' => $withRecord->id,
            'date' => $date,
            'check_in' => '08:05:00',
            'check_out' => '15:30:00',
            'status' => 'present',
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/daily?date='.$date);

        $response->assertStatus(200)
            ->assertJsonPath('total_employees', 2);

        $rows = collect($response->json('records'));

        $this->assertEquals(1, $rows->where('status', 'present')->count());
        $this->assertEquals(1, $rows->where('status', 'absent')->count());

        $absent = $rows->firstWhere('employee.id', $withoutRecord->id);
        $this->assertNull($absent['attendance']);
        $this->assertFalse($absent['has_record']);
        $this->assertEquals('absent', $absent['status']);

        $present = $rows->firstWhere('employee.id', $withRecord->id);
        $this->assertTrue($present['has_record']);
        $this->assertEquals('present', $present['status']);
    }

    public function test_daily_book_defaults_to_today_and_respects_exempt_departments()
    {
        $date = now()->toDateString();

        $regular = $this->makeEmployee();

        $exemptDepartment = Department::factory()->create(['code' => 'GAB-GOV']);
        $exemptPosition = Position::factory()->create(['department_id' => $exemptDepartment->id]);
        $exempt = $this->makeEmployee([
            'department_id' => $exemptDepartment->id,
            'position_id' => $exemptPosition->id,
        ]);

        $response = $this->getJsonAuth('/api/rh/attendance/daily');

        $response->assertStatus(200)
            ->assertJsonPath('date', $date)
            ->assertJsonFragment(['id' => $regular->id])
            ->assertJsonMissing(['id' => $exempt->id]);
    }

    /** Configuração do livro de ponto */
    public function test_configuration_get_returns_default_empty()
    {
        $response = $this->getJsonAuth('/api/rh/attendance/configuration');

        $response->assertStatus(200)
            ->assertJsonPath('configured', false)
            ->assertJsonPath('attendance_book_departments', []);
    }

    public function test_configuration_put_persists_and_affects_listings()
    {
        $date = now()->toDateString();

        $otherDepartment = Department::factory()->create();
        $otherPosition = Position::factory()->create(['department_id' => $otherDepartment->id]);

        $inBook = $this->makeEmployee();
        $outside = $this->makeEmployee([
            'department_id' => $otherDepartment->id,
            'position_id' => $otherPosition->id,
        ]);

        $response = $this->putJson('/api/rh/attendance/configuration', [
            'attendance_book_departments' => [$this->department->id],
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonPath('configured', true)
            ->assertJsonPath('attendance_book_departments', [$this->department->id]);

        $get = $this->getJsonAuth('/api/rh/attendance/configuration');
        $get->assertJsonPath('attendance_book_departments', [$this->department->id]);

        $employees = $this->getJsonAuth('/api/rh/attendance/employees?date='.$date);
        $employees->assertJsonFragment(['id' => $inBook->id])
            ->assertJsonMissing(['id' => $outside->id]);
    }

    public function test_configuration_put_with_empty_list_clears_config()
    {
        AttendanceBookConfig::create(['attendance_book_departments' => [$this->department->id]]);

        $response = $this->putJson('/api/rh/attendance/configuration', [
            'attendance_book_departments' => [],
        ], $this->headers);

        $response->assertStatus(200)
            ->assertJsonPath('configured', false)
            ->assertJsonPath('attendance_book_departments', []);

        $get = $this->getJsonAuth('/api/rh/attendance/configuration');
        $get->assertJsonPath('configured', false);
    }
}