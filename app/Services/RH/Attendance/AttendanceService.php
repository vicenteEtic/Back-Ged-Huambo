<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\AttendanceImportLog;
use App\Models\RH\Attendance\Shift;
use App\Models\RH\Attendance\ShiftAssignment;
use App\Repositories\RH\Attendance\AttendanceRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService extends AbstractService
{
    public function __construct(AttendanceRepository $repository)
    {
        parent::__construct($repository);
    }

    public function registerCheckIn(int $employeeId, string $date, string $time): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $time) {
            $shift = $this->resolveShift($employeeId, $date);
            $expectedIn = $shift ? Carbon::parse($shift->start_time) : null;
            $actualIn = Carbon::parse($time);
            $lateMinutes = 0;

            if ($expectedIn) {
                $graceEnd = $expectedIn->copy()->addMinutes($shift->grace_minutes);
                if ($actualIn->gt($graceEnd)) {
                    $lateMinutes = $actualIn->diffInMinutes($expectedIn);
                }
            }

            $data = [
                'employee_id' => $employeeId,
                'date' => $date,
                'check_in' => $time,
                'status' => $lateMinutes > 0 ? 'late' : 'present',
                'shift_id' => $shift?->id,
                'expected_check_in' => $shift?->start_time,
                'late_minutes' => $lateMinutes,
            ];

            return Attendance::updateOrCreate(
                ['employee_id' => $employeeId, 'date' => $date],
                $data
            );
        });
    }

    public function registerCheckOut(int $employeeId, string $date, string $time): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $time) {
            $record = Attendance::where('employee_id', $employeeId)->where('date', $date)->firstOrFail();
            $shift = $record->shift;

            $checkIn = Carbon::parse($record->check_in);
            $checkOut = Carbon::parse($time);
            $hoursWorked = round($checkIn->diffInMinutes($checkOut) / 60, 2);

            $overtimeMinutes = 0;
            if ($shift) {
                $expectedOut = Carbon::parse($shift->end_time);
                if ($checkOut->gt($expectedOut)) {
                    $overtimeMinutes = $checkOut->diffInMinutes($expectedOut);
                }
            }

            $record->update([
                'check_out' => $time,
                'hours_worked' => $hoursWorked,
                'overtime_minutes' => $overtimeMinutes,
                'expected_check_out' => $shift?->end_time,
            ]);

            return $record->fresh();
        });
    }

    public function registerAbsence(int $employeeId, string $date, string $type, ?string $reason = null, bool $justified = false): Attendance
    {
        return Attendance::updateOrCreate(
            ['employee_id' => $employeeId, 'date' => $date],
            [
                'status' => 'absent',
                'absence_type' => $type,
                'absence_reason' => $reason,
                'is_justified' => $justified,
            ]
        );
    }

    public function monthlyReport(int $employeeId, int $year, int $month): array
    {
        $records = Attendance::where('employee_id', $employeeId)
            ->whereYear('date', $year)
            ->whereMonth('date', $month)
            ->orderBy('date')
            ->get();

        $summary = [
            'employee_id' => $employeeId,
            'year' => $year,
            'month' => $month,
            'total_days' => $records->count(),
            'present' => $records->where('status', 'present')->count(),
            'late' => $records->where('status', 'late')->count(),
            'absent' => $records->where('status', 'absent')->count(),
            'total_late_minutes' => $records->sum('late_minutes'),
            'total_overtime_minutes' => $records->sum('overtime_minutes'),
            'total_hours_worked' => round($records->sum('hours_worked'), 2),
            'records' => $records,
        ];

        return $summary;
    }

    public function importBiometric(array $rows, string $filename): AttendanceImportLog
    {
        return DB::transaction(function () use ($rows, $filename) {
            $total = count($rows);
            $imported = 0;
            $failed = 0;
            $errors = [];

            foreach ($rows as $index => $row) {
                try {
                    $employee = \App\Models\RH\Employee\Employee::where('employee_number', $row['employee_number'] ?? '')->first();
                    if (!$employee) {
                        throw new \Exception("Funcionário não encontrado: {$row['employee_number']}");
                    }

                    $date = $row['date'] ?? now()->toDateString();
                    $record = Attendance::firstOrNew(['employee_id' => $employee->id, 'date' => $date]);

                    if (!empty($row['check_in'])) {
                        $record->check_in = $row['check_in'];
                    }
                    if (!empty($row['check_out'])) {
                        $record->check_out = $row['check_out'];
                    }

                    if ($record->check_in && $record->check_out) {
                        $checkIn = Carbon::parse($record->check_in);
                        $checkOut = Carbon::parse($record->check_out);
                        $record->hours_worked = round($checkIn->diffInMinutes($checkOut) / 60, 2);
                        $record->status = 'present';
                    }

                    $record->save();
                    $imported++;
                } catch (\Exception $e) {
                    $failed++;
                    $errors[] = "Linha {$index}: {$e->getMessage()}";
                }
            }

            return AttendanceImportLog::create([
                'filename' => $filename,
                'total_rows' => $total,
                'imported_rows' => $imported,
                'failed_rows' => $failed,
                'error_log' => implode("\n", $errors),
                'imported_by' => auth()->id(),
            ]);
        });
    }

    private function resolveShift(int $employeeId, string $date): ?Shift
    {
        $assignment = ShiftAssignment::where('employee_id', $employeeId)
            ->where('effective_date', '<=', $date)
            ->where(function ($q) use ($date) {
                $q->whereNull('end_date')->orWhere('end_date', '>=', $date);
            })
            ->orderByDesc('effective_date')
            ->first();

        return $assignment?->shift;
    }
}
