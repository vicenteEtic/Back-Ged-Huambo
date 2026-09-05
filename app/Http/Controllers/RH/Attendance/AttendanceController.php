<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\RH\Attendance\AttendanceRequest;
use App\Models\RH\Attendance\AbsenceType;
use App\Services\RH\Attendance\AttendanceService;
use App\Support\TimeNormalizer;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
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
            fn () => $this->service->store($request->validated()),
        );
    }

    public function update(AttendanceRequest $request, $id)
    {
        return $this->handleUpdate(
            fn () => $this->service->update($request->validated(), $id),
            $id,
        );
    }

    /**
     * Listagem de assiduidade (comportamento clássico).
     * Devolve a listagem paginada de registos com as relações pedidas
     * (ex.: employee); relações antigas como `shift` (removida em Set 2026)
     * são ignoradas em vez de devolver erro.
     */
    public function index(Request $request)
    {
        try {
            if ($this->logRequest) {
                $this->logRequest();
                $this->logToDatabase(
                    type: $this->logType,
                    level: 'info',
                    customMessage: 'O utilizador '.Auth::user()->first_name.' visualizou todos os registos no módulo '.$this->nameEntity,
                );
            }

            $filters = $request['filters'] ?? $request['filtersV2'];
            $relationships = $this->legacyRelationships($request['relationships']);
            $service = $this->service->index($request['paginate'], $filters, $request['orderBy'], $relationships);

            return response()->json($service);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            if ($this->logRequest) {
                $this->logRequest($e);
            }
            Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Filtra as relações pedidas pela listagem, ignorando silenciosamente
     * relações que já não existem no modelo (ex.: `shift`, removida em Set 2026).
     */
    protected function legacyRelationships($relationships): array
    {
        $requested = is_array($relationships) ? $relationships : [];

        return array_values(array_filter($requested, static function ($relation) {
            return method_exists(\App\Models\RH\Attendance\Attendance::class, \Illuminate\Support\Str::camel($relation));
        }));
    }

    /**
     * Assiduidade de um funcionário num período
     * (1 dia, 3 dias, 1 semana, 1 mês, 3 meses, 6 meses, 1 ano).
     */
    public function employeeAssiduidade(Request $request, int $employeeId)
    {
        try {
            $result = $this->attendanceService->employeeAssiduidade(
                $employeeId,
                $request->only(['date', 'period', 'start_date', 'end_date'])
            );

            return response()->json($result);
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Funcionário não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            Log::error('Erro ao consultar assiduidade do funcionário', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function checkIn(Request $request)
    {
        try {
            $request->merge(['check_in' => TimeNormalizer::normalize($request->input('check_in'))]);
            $request->validate([
                'employee_id' => 'required|exists:employees,id',
                'date' => 'required|date',
                'check_in' => 'required|date_format:H:i:s',
                'notes' => 'nullable|string',
            ]);
            $attendance = $this->attendanceService->registerCheckIn(
                $request->employee_id, $request->date, $request->check_in, $request->input('notes')
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
                'notes' => 'nullable|string',
            ]);
            $attendance = $this->attendanceService->registerCheckOut(
                $request->employee_id, $request->date, $request->check_out, $request->input('notes')
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

            return response()->json($this->attendanceService->absences(
                $year,
                $month,
                $employeeId,
                $departmentId,
                $request->integer('paginate') ?: null
            ));
        } catch (\InvalidArgumentException $e) {
            return response()->json(['error' => $e->getMessage()], Response::HTTP_BAD_REQUEST);
        } catch (Exception $e) {
            Log::error('Erro ao listar faltas', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    public function employeesForPoint(Request $request)
    {
        try {
            $date = $request->input('date', now()->toDateString());
            $departmentId = $request->input('department_id') ? (int) $request->input('department_id') : null;

            $employees = $this->attendanceService->listEmployeesForPoint($date, $departmentId);

            return response()->json([
                'date' => \Carbon\Carbon::parse($date)->format('Y-m-d'),
                'message' => 'Funcionários em férias ou com dispensa aprovada estão identificados e bloqueados para registo de ponto.',
                'blocked_count' => collect($employees)->where('on_leave', true)->count() + collect($employees)->where('on_dispensa', true)->count(),
                'employees' => $employees,
            ]);
        } catch (Exception $e) {
            Log::error('Erro ao listar funcionários para ponto', ['message' => $e->getMessage()]);

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
                $request->input('filename', 'biometric_import_'.now()->format('Ymd_His'))
            );

            return response()->json($result, Response::HTTP_CREATED);
        } catch (Exception $e) {
            Log::error('Erro ao importar dados biométricos', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Endpoint de compatibilidade para o módulo de turnos (shifts/shift_assignments),
     * removido em Set 2026. O frontend legado ainda o invoca; devolve a listagem
     * vazia no mesmo formato da listagem clássica.
     */
    public function removedFeature(Request $request)
    {
        try {
            $this->logRequest();

            return response()->json([]);
        } catch (Exception $e) {
            $this->logRequest($e);
            Log::error('Erro ao consultar funcionalidade removida', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }
}
