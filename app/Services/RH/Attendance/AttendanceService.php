<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\AttendanceImportLog;
use App\Models\RH\Attendance\Shift;
use App\Models\RH\Attendance\ShiftAssignment;
use App\Repositories\RH\Attendance\AttendanceRepository;
use App\Services\AbstractService;
use App\Support\TimeNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService extends AbstractService
{
    public function __construct(AttendanceRepository $repository)
    {
        parent::__construct($repository);
    }

    public function store(array $data)
    {
        return DB::transaction(function () use ($data) {
            $data = $this->applyCalculations($data);

            $model = $this->repository->store($data);

            return $model->fresh();
        });
    }

    public function update(array $data, int $id)
    {
        return DB::transaction(function () use ($data, $id) {
            $record = $this->repository->show($id);

            $data['employee_id'] = $data['employee_id'] ?? $record->employee_id;
            $data['date'] = $data['date'] ?? $record->date;
            $data['shift_id'] = $data['shift_id'] ?? $record->shift_id;
            $data['check_in'] = $data['check_in'] ?? $record->check_in;
            $data['check_out'] = $data['check_out'] ?? $record->check_out;

            $data = $this->applyCalculations($data);

            return $this->repository->update($data, $id);
        });
    }

    private function applyCalculations(array $data): array
    {
        $checkIn = TimeNormalizer::normalize($data['check_in'] ?? null);
        $checkOut = TimeNormalizer::normalize($data['check_out'] ?? null);

        if (!$checkIn && !$checkOut) {
            return $data;
        }

        $data['check_in'] = $checkIn;
        $data['check_out'] = $checkOut;

        $shift = null;
        if (!empty($data['shift_id'])) {
            $shift = Shift::find($data['shift_id']);
        } elseif (!empty($data['employee_id']) && !empty($data['date'])) {
            $shift = $this->resolveShift((int) $data['employee_id'], $data['date']);
        }

        if ($checkIn && $checkOut) {
            $data['hours_worked'] = round(Carbon::parse($checkIn)->diffInMinutes(Carbon::parse($checkOut)) / 60, 2);
        }

        if ($shift && $checkIn) {
            $expectedIn = Carbon::parse($shift->start_time);
            $actualIn = Carbon::parse($checkIn);
            $graceEnd = $expectedIn->copy()->addMinutes($shift->grace_minutes);

            $data['late_minutes'] = $actualIn->gt($graceEnd)
                ? max(0, (int) round($expectedIn->diffInMinutes($actualIn)))
                : 0;
            $data['expected_check_in'] = $shift->start_time;

            if (empty($data['absence_type'])) {
                $data['status'] = $data['late_minutes'] > 0 ? 'late' : 'present';
            }
        }

        if ($shift && $checkOut) {
            $expectedOut = Carbon::parse($shift->end_time);
            $actualOut = Carbon::parse($checkOut);

            $data['overtime_minutes'] = $actualOut->gt($expectedOut)
                ? max(0, (int) round($expectedOut->diffInMinutes($actualOut)))
                : 0;
            $data['expected_check_out'] = $shift->end_time;
        }

        return $data;
    }

    public function registerCheckIn(int $employeeId, string $date, string $time): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $time) {
            $date = Carbon::parse($date)->format('Y-m-d');
            $shift = $this->resolveShift($employeeId, $date);
            $expectedIn = $shift ? Carbon::parse($shift->start_time) : null;
            $actualIn = Carbon::parse(TimeNormalizer::normalize($time) ?? $time);
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
                'check_in' => TimeNormalizer::normalize($time),
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
            $date = Carbon::parse($date)->format('Y-m-d');
            $record = Attendance::where('employee_id', $employeeId)->where('date', $date)->firstOrFail();
            $shift = $record->shift;

            $checkIn = Carbon::parse(TimeNormalizer::normalize($record->check_in) ?? $record->check_in);
            $checkOut = Carbon::parse(TimeNormalizer::normalize($time) ?? $time);
            $hoursWorked = round($checkIn->diffInMinutes($checkOut) / 60, 2);

            $overtimeMinutes = 0;
            if ($shift) {
                $expectedOut = Carbon::parse($shift->end_time);
                if ($checkOut->gt($expectedOut)) {
                    $overtimeMinutes = $checkOut->diffInMinutes($expectedOut);
                }
            }

            $record->update([
                'check_out' => TimeNormalizer::normalize($time),
                'hours_worked' => $hoursWorked,
                'overtime_minutes' => $overtimeMinutes,
                'expected_check_out' => $shift?->end_time,
            ]);

            return $record->fresh();
        });
    }

    public function registerAbsence(int $employeeId, string $date, string $type, ?string $reason = null, bool $justified = false): Attendance
    {
        $date = Carbon::parse($date)->format('Y-m-d');

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

                    $date = !empty($row['date'])
                        ? Carbon::parse($row['date'])->format('Y-m-d')
                        : now()->toDateString();
                    $record = Attendance::firstOrNew(['employee_id' => $employee->id, 'date' => $date]);

                    if (!empty($row['check_in'])) {
                        $record->check_in = TimeNormalizer::normalize($row['check_in']);
                    }
                    if (!empty($row['check_out'])) {
                        $record->check_out = TimeNormalizer::normalize($row['check_out']);
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
