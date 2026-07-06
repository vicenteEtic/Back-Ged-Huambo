<?php

namespace Tests\Feature\RH\Attendance;

use Tests\Feature\RH\RhTestCase;
use App\Models\RH\Attendance\Shift;
use App\Models\RH\Attendance\ShiftAssignment;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Department\Department;
use App\Models\RH\Position\Position;

class AttendanceImportLogTest extends RhTestCase
{
    public function test_can_import_biometric_data()
    {
        $department = Department::factory()->create();
        $position = Position::factory()->create(['department_id' => $department->id]);
        $employee = Employee::factory()->create([
            'department_id' => $department->id,
            'position_id' => $position->id,
        ]);
        $shift = Shift::factory()->create();
        ShiftAssignment::factory()->create([
            'employee_id' => $employee->id,
            'shift_id' => $shift->id,
        ]);

        $data = [
            'rows' => [
                [
                    'employee_number' => $employee->numero_funcionario,
                    'date' => now()->format('Y-m-d'),
                    'check_in' => '08:00:00',
                    'check_out' => '17:00:00',
                ],
            ],
            'filename' => 'test_biometric.csv',
        ];

        $response = $this->postJsonAuth('/api/rh/attendance/import-biometric', $data);
        $this->assertTrue(in_array($response->status(), [200, 201]));
    }
}
