<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\AttendanceRequest;
use App\Models\RH\Attendance\AbsenceType;
use App\Services\RH\Attendance\AttendanceService;
use App\Support\TimeNormalizer;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class AttendanceController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Assiduidade';
    protected ?string $fieldName = 'id';

    public function __construct(
        AttendanceService $service,
        protected AttendanceService $attendanceService
    ) {
        $this->service = $service;
    }

    public function store(AttendanceRequest $request)
    {
        return $this->handleStore(
            fn() => $this->service->store($request->validated()),
        );
    }

    public function update(AttendanceRequest $request, $id)
    {
        return $this->handleUpdate(
            fn() => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    public function checkIn(Request $request)
    {
        try {
            $request->merge(['check_in' => TimeNormalizer::normalize($request->input('check_in'))]);
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date',
                'check_in' => 'required|date_format:H:i:s',
            ]);
            $attendance = $this->attendanceService->registerCheckIn(
                $request->employee_id, $request->date, $request->check_in
            );
            return response()->json($attendance);
        } catch (Exception $e) {
            Log::error('Erro ao registar entrada', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function checkOut(Request $request)
    {
        try {
            $request->merge(['check_out' => TimeNormalizer::normalize($request->input('check_out'))]);
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date',
                'check_out' => 'required|date_format:H:i:s',
            ]);
            $attendance = $this->attendanceService->registerCheckOut(
                $request->employee_id, $request->date, $request->check_out
            );
            return response()->json($attendance);
        } catch (Exception $e) {
            Log::error('Erro ao registar saída', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function monthlyReport(Request $request, int $employeeId)
    {
        try {
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            return response()->json($this->attendanceService->monthlyReport($employeeId, $year, $month));
        } catch (Exception $e) {
            Log::error('Erro ao gerar relatório', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function absenceTypes(Request $request)
    {
        $types = AbsenceType::where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'code', 'name', 'description']);

        return response()->json([
            'types' => $types,
        ]);
    }

    public function absences(Request $request)
    {
        try {
            $year = $request->integer('year') ?: now()->year;
            $month = $request->integer('month') ?: now()->month;
            $employeeId = $request->input('employee_id') ? (int) $request->input('employee_id') : null;
            $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;

            return response()->json($this->attendanceService->absences($year, $month, $employeeId, $departmentId));
        } catch (Exception $e) {
            Log::error('Erro ao listar faltas', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function importBiometric(Request $request)
    {
        try {
            $rows = collect($request->rows ?? [])->map(function ($row) {
                $row['check_in'] = TimeNormalizer::normalize($row['check_in'] ?? null);
                $row['check_out'] = TimeNormalizer::normalize($row['check_out'] ?? null);

                return $row;
            })->all();

            $request->merge(['rows' => $rows]);

            $request->validate([
                'rows' => 'required|array',
                'rows.*.employee_number' => 'required|string',
                'rows.*.date' => 'nullable|date',
                'rows.*.check_in' => 'nullable|date_format:H:i:s',
                'rows.*.check_out' => 'nullable|date_format:H:i:s',
                'filename' => 'nullable|string|max:255',
            ]);

            $result = $this->attendanceService->importBiometric(
                $request->rows,
                $request->input('filename', 'biometric_import_' . now()->format('Ymd_His'))
            );
            return response()->json($result, Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Erro ao importar dados biométricos', ['message' => $e->getMessage()]);
            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
