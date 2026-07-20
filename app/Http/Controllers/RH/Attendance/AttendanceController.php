<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\AttendanceRequest;
use App\Services\RH\Attendance\AttendanceService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AttendanceController extends AbstractController
{
    protected ?string $logType = 'rh';
    protected ?string $nameEntity = 'Attendance';
    protected ?string $fieldName = 'id';

    public function __construct(
        AttendanceService $service,
        protected AttendanceService $attendanceService
    ) {
        $this->service = $service;
    }

    public function store(AttendanceRequest $request)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $attendance = $this->service->store($request->validated());
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Attendance recorded by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($attendance, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error recording attendance', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(AttendanceRequest $request, $id)
    {
        DB::beginTransaction();
        try {
            $this->logRequest();
            $attendance = $this->service->update($request->validated(), $id);
            $this->logToDatabase(
                type: 'rh', level: 'info',
                customMessage: 'Attendance updated by ' . auth()->user()->first_name
            );
            DB::commit();
            return response()->json($attendance, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            DB::rollBack();
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            Log::error('Error updating attendance', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkIn(Request $request)
    {
        try {
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
            Log::error('Error registering check-in', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function checkOut(Request $request)
    {
        try {
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
            Log::error('Error registering check-out', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function absence(Request $request)
    {
        try {
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date',
                'absence_type' => 'required|string|max:100',
                'absence_reason' => 'nullable|string',
                'is_justified' => 'boolean',
            ]);
            $attendance = $this->attendanceService->registerAbsence(
                $request->employee_id, $request->date, $request->absence_type,
                $request->absence_reason, $request->boolean('is_justified')
            );
            return response()->json($attendance);
        } catch (Exception $e) {
            Log::error('Error registering absence', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function monthlyReport(Request $request, int $employeeId)
    {
        try {
            $year = $request->input('year', now()->year);
            $month = $request->input('month', now()->month);
            return response()->json($this->attendanceService->monthlyReport($employeeId, $year, $month));
        } catch (Exception $e) {
            Log::error('Error generating report', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function importBiometric(Request $request)
    {
        try {
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
            Log::error('Error importing biometric data', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
