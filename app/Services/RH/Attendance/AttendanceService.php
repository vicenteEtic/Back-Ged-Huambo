<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Attendance\AttendanceImportLog;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveRequest;
use App\Repositories\RH\Attendance\AttendanceRepository;
use App\Services\AbstractService;
use App\Services\RH\Leave\HolidayService;
use App\Services\RH\Leave\LeaveRequestService;
use App\Support\Dispensa;
use App\Support\PontoExceptions;
use App\Support\TimeNormalizer;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class AttendanceService extends AbstractService
{
    public const PERIODS = [
        'today' => 'Hoje',
        'yesterday' => 'Ontem',
        'day_before_yesterday' => 'Anteontem',
        'this_week' => 'Esta semana',
        'last_week' => 'Semana anterior',
        'this_month' => 'Este mês',
        'last_month' => 'Mês anterior',
        'last_3_months' => 'Últimos 3 meses',
        'last_6_months' => 'Últimos 6 meses',
        'this_year' => 'Este ano',
        'custom' => 'Intervalo personalizado',
    ];

    public const EMPLOYEE_PERIODS = [
        '1_day' => '1 dia',
        '3_days' => '3 dias',
        '1_week' => '1 semana',
        '1_month' => '1 mês',
        '3_months' => '3 meses',
        '6_months' => '6 meses',
        '1_year' => '1 ano',
    ];

    public function __construct(
        AttendanceRepository $repository,
        private readonly ?HolidayService $holidayService = null,
        private readonly ?LeaveRequestService $leaveRequestService = null
    ) {
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

            if ($record->status === 'absent' && (isset($data['check_in']) || isset($data['check_out']))) {
                throw new \DomainException('Funcionário registado como ausente nesta data: para registar o ponto, primeiro justifique a falta.');
            }

            if (Dispensa::approvedFullDayForDate($record->employee_id, (string) $record->date)) {
                throw new \DomainException('Funcionário com dispensa aprovada nesta data: não é permitido registar o ponto.');
            }

            $data['employee_id'] = $data['employee_id'] ?? $record->employee_id;
            $data['date'] = $data['date'] ?? $record->date;
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

        if (! $checkIn && ! $checkOut) {
            return $data;
        }

        $data['check_in'] = $checkIn;
        $data['check_out'] = $checkOut;

        if ($checkIn && $checkOut) {
            $data['hours_worked'] = round(Carbon::parse($checkIn)->diffInMinutes(Carbon::parse($checkOut)) / 60, 2);
        }

        // Sem turnos: atrasos e horas extra não são calculados, valores neutros
        $data['late_minutes'] = 0;
        $data['overtime_minutes'] = 0;
        $data['expected_check_in'] = null;
        $data['expected_check_out'] = null;
        $data['shift_id'] = null;

        if ($checkIn && empty($data['absence_type'])) {
            $data['status'] = 'present';
        }

        return $data;
    }

    public function registerCheckIn(int $employeeId, string $date, string $time, ?string $note = null): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $time, $note) {
            $date = Carbon::parse($date)->format('Y-m-d');

            $this->assertNotExemptFromPonto($employeeId);
            $this->assertNotOnLeave($employeeId, $date);
            $this->assertNotOnFullDayDispensa($employeeId, $date);
            $this->assertNotAbsent($employeeId, $date);

            $data = [
                'employee_id' => $employeeId,
                'date' => $date,
                'check_in' => TimeNormalizer::normalize($time),
                'status' => 'present',
                'late_minutes' => 0,
                'overtime_minutes' => 0,
                'expected_check_in' => null,
                'expected_check_out' => null,
                'shift_id' => null,
            ];

            if ($note !== null) {
                $data['notes'] = $note;
            }

            $record = $this->upsertForDate($employeeId, $date, $data);

            $partialDispensa = Dispensa::approvedForDate($employeeId, $date);

            if ($partialDispensa && Dispensa::isBreastfeeding($partialDispensa) && $record->check_in) {
                $record->update([
                    'attendance_request_id' => $partialDispensa->id,
                    'expected_check_in' => config('rh.dispensa.work_start', '08:00'),
                    'expected_check_out' => Dispensa::expectedCheckoutWithBreastfeeding($record->check_in),
                ]);

                $record = $record->fresh();
            }

            return $record;
        });
    }

    public function registerCheckOut(int $employeeId, string $date, string $time, ?string $note = null): Attendance
    {
        return DB::transaction(function () use ($employeeId, $date, $time, $note) {
            $date = Carbon::parse($date)->format('Y-m-d');

            $this->assertNotExemptFromPonto($employeeId);
            $this->assertNotOnLeave($employeeId, $date);
            $this->assertNotOnFullDayDispensa($employeeId, $date);
            $this->assertNotAbsent($employeeId, $date);

            $record = Attendance::where('employee_id', $employeeId)->where('date', $date)->firstOrFail();

            $checkIn = Carbon::parse(TimeNormalizer::normalize($record->check_in) ?? $record->check_in);
            $checkOut = Carbon::parse(TimeNormalizer::normalize($time) ?? $time);
            $hoursWorked = round($checkIn->diffInMinutes($checkOut) / 60, 2);

            $data = [
                'check_out' => TimeNormalizer::normalize($time),
                'hours_worked' => $hoursWorked,
                'overtime_minutes' => 0,
                'expected_check_out' => null,
                'shift_id' => null,
            ];

            if ($note !== null) {
                $data['notes'] = $note;
            }

            $record->update($data);

            return $record->fresh();
        });
    }

    public function registerAbsence(int $employeeId, string $date, string $type, ?string $reason = null, bool $justified = false): Attendance
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $this->assertNotExemptFromPonto($employeeId);
        $this->assertNotOnLeave($employeeId, $date);
        $this->assertNotOnFullDayDispensa($employeeId, $date);

        return $this->upsertForDate($employeeId, $date, [
            'employee_id' => $employeeId,
            'date' => $date,
            'status' => 'absent',
            'absence_type' => $type,
            'absence_reason' => $reason,
            'is_justified' => $justified,
        ]);
    }

    private function upsertForDate(int $employeeId, string $date, array $data): Attendance
    {
        $record = Attendance::withTrashed()
            ->where('employee_id', $employeeId)
            ->where('date', $date)
            ->first();

        if ($record) {
            if ($record->trashed()) {
                $record->restore();
            }
            $record->fill($data)->save();

            return $record;
        }

        return Attendance::create($data);
    }

    /**
     * Lança excepção se o funcionário estiver de férias (licença aprovada) na data.
     */
    private function assertNotOnLeave(int $employeeId, string $date): void
    {
        if ($this->leaveRequestService && $this->leaveRequestService->isOnLeave($employeeId, $date)) {
            throw new \DomainException('Funcionário de férias: não é permitido registar ponto nesta data.');
        }
    }

    /**
     * Lança excepção se o departamento do funcionário tiver excepção ao livro
     * de ponto do RH (gabinetes com livro próprio — ver config/rh.php).
     */
    private function assertNotExemptFromPonto(int $employeeId): void
    {
        $employee = Employee::with('department')->find($employeeId);

        if ($employee && PontoExceptions::isEmployeeExempt($employee)) {
            throw new \DomainException('Gabinete com excepção no livro de ponto: este funcionário não assina o ponto no RH.');
        }
    }

    /**
     * Lança excepção se o funcionário possui dispensa aprovada de dia inteiro na data.
     */
    private function assertNotOnFullDayDispensa(int $employeeId, string $date): void
    {
        if (Dispensa::approvedFullDayForDate($employeeId, $date)) {
            throw new \DomainException('Funcionário com dispensa aprovada nesta data: não é permitido registar o ponto.');
        }
    }

    /**
     * Lança excepção se já existe registo de falta (ausente) para a data —
     * impede registar horários sobre uma falta registada.
     */
    private function assertNotAbsent(int $employeeId, string $date): void
    {
        $record = Attendance::where('employee_id', $employeeId)->where('date', $date)->first();

        if ($record && $record->status === 'absent') {
            throw new \DomainException('Funcionário registado como ausente nesta data: para registar o ponto, primeiro justifique a falta.');
        }
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
            'dispensado' => $records->where('status', 'dispensado')->count(),
            'total_late_minutes' => $records->sum('late_minutes'),
            'total_overtime_minutes' => $records->sum('overtime_minutes'),
            'total_hours_worked' => round($records->sum('hours_worked'), 2),
            'records' => $records,
        ];

        return $summary;
    }

    /**
     * Lista as faltas de um mês, agrupadas por funcionário.
     * Permite filtrar por funcionário e por departamento.
     */
    public function absences(?int $year = null, ?int $month = null, ?int $employeeId = null, ?int $departmentId = null): array
    {
        $year = $year ?? now()->year;
        $month = $month ?? now()->month;

        $query = Attendance::where('status', 'absent')
            ->whereYear('date', $year)
            ->whereMonth('date', $month);

        if ($employeeId) {
            $query->where('employee_id', $employeeId);
        }

        $records = $query->with(['employee', 'employee.department'])
            ->orderBy('date')
            ->get();

        if ($departmentId) {
            $records = $records->filter(fn ($record) => $record->employee?->department_id === $departmentId);
        }

        $byEmployee = $records->groupBy('employee_id')->map(function ($items) {
            $first = $items->first();

            return [
                'employee_id' => $first->employee_id,
                'employee' => $first->employee,
                'total_absences' => $items->count(),
                'justified' => $items->where('is_justified', true)->count(),
                'unjustified' => $items->where('is_justified', false)->count(),
                'dates' => $items->pluck('date')->map(fn ($d) => $d->format('Y-m-d'))->values(),
            ];
        })->values();

        $types = \App\Models\RH\Attendance\AbsenceType::whereIn('code', $records->pluck('absence_type')->unique()->filter())
            ->get(['code', 'name'])
            ->keyBy('code');

        $byType = $records->groupBy('absence_type')->map(function ($items, $type) use ($types) {
            return [
                'type' => $type,
                'name' => $types->get($type)?->name ?? $type,
                'total' => $items->count(),
                'justified' => $items->where('is_justified', true)->count(),
                'unjustified' => $items->where('is_justified', false)->count(),
            ];
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'filters' => [
                'employee_id' => $employeeId,
                'department_id' => $departmentId,
            ],
            'total_absences' => $records->count(),
            'total_employees' => $byEmployee->count(),
            'by_employee' => $byEmployee,
            'by_type' => $byType,
        ];
    }

    /**
     * Marca automaticamente como falta todos os funcionários activos que não
     * possuem registo de ponto na data indicada (default: ontem).
     * Dias não úteis (fim-de-semana/feriado) são ignorados.
     */
    public function markAbsentForDate(string $date): array
    {
        $date = Carbon::parse($date)->format('Y-m-d');

        $target = Carbon::parse($date);

        if ($target->isWeekend()) {
            return ['marked' => 0, 'skipped' => 'weekend', 'date' => $date];
        }

        if ($this->holidayService?->isHoliday($target) ?? false) {
            return ['marked' => 0, 'skipped' => 'holiday', 'date' => $date];
        }

        $employees = Employee::where('status', 'active')
            ->where('hire_date', '<=', $date)
            ->with('department')
            ->get();

        $withRecord = Attendance::whereDate('date', $date)
            ->pluck('employee_id');

        $approvedDispensas = \App\Models\RH\Attendance\AttendanceRequest::query()
            ->where('status', 'approved')
            ->where('benefit_active', true)
            ->where('applies_full_day', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get(['id', 'employee_id', 'reason']);

        $dispensaByEmployee = $approvedDispensas->keyBy('employee_id');

        $marked = 0;
        $onLeave = 0;
        $exempt = 0;
        $dispensado = 0;
        foreach ($employees as $employee) {
            if ($withRecord->contains($employee->id)) {
                continue;
            }

            if (PontoExceptions::isEmployeeExempt($employee)) {
                $exempt++;

                continue;
            }

            if ($this->leaveRequestService?->isOnLeave($employee->id, $date) ?? false) {
                $onLeave++;

                continue;
            }

            $dispensa = $dispensaByEmployee->get($employee->id);

            if ($dispensa) {
                Attendance::create([
                    'employee_id' => $employee->id,
                    'date' => $date,
                    'status' => 'dispensado',
                    'attendance_request_id' => $dispensa->id,
                    'absence_reason' => $dispensa->reason,
                    'notes' => 'Dispensa aprovada: dispensado por solicitação aprovada.',
                    'is_justified' => true,
                ]);
                $dispensado++;

                continue;
            }

            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'status' => 'absent',
                'absence_type' => 'injustificada',
                'absence_reason' => 'Falta registada automaticamente por ausência de ponto no dia.',
                'is_justified' => false,
            ]);
            $marked++;
        }

        return ['marked' => $marked, 'skipped' => null, 'date' => $date, 'total_employees' => $employees->count(), 'on_leave_skipped' => $onLeave, 'exempt_skipped' => $exempt, 'dispensa_skipped' => $dispensado];
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
                    $employee = \App\Models\RH\Employee\Employee::with('department')->where('employee_number', $row['employee_number'] ?? '')->first();
                    if (! $employee) {
                        throw new \Exception("Funcionário não encontrado: {$row['employee_number']}");
                    }

                    $date = ! empty($row['date'])
                        ? Carbon::parse($row['date'])->format('Y-m-d')
                        : now()->toDateString();

                    if (PontoExceptions::isEmployeeExempt($employee)) {
                        throw new \Exception('Gabinete com excepção no livro de ponto: este funcionário não assina o ponto no RH.');
                    }

                    if ($this->leaveRequestService?->isOnLeave($employee->id, $date) ?? false) {
                        throw new \Exception('Funcionário de férias: não é permitido registar ponto nesta data.');
                    }

                    if (Dispensa::approvedFullDayForDate($employee->id, $date)) {
                        throw new \Exception('Funcionário com dispensa aprovada nesta data: não é permitido registar o ponto.');
                    }
                    $record = Attendance::firstOrNew(['employee_id' => $employee->id, 'date' => $date]);

                    if ($record->exists && $record->status === 'absent') {
                        throw new \Exception('Funcionário registado como ausente nesta data: primeiro justifique a falta.');
                    }

                    if (! empty($row['check_in'])) {
                        $record->check_in = TimeNormalizer::normalize($row['check_in']);
                    }
                    if (! empty($row['check_out'])) {
                        $record->check_out = TimeNormalizer::normalize($row['check_out']);
                    }

                    if ($record->check_in && $record->check_out) {
                        $checkIn = Carbon::parse($record->check_in);
                        $checkOut = Carbon::parse($record->check_out);
                        $record->hours_worked = round($checkIn->diffInMinutes($checkOut) / 60, 2);
                        $record->status = 'present';
                    }

                    $partialDispensa = Dispensa::approvedForDate($employee->id, $date);

                    if ($partialDispensa && Dispensa::isBreastfeeding($partialDispensa) && ! empty($record->check_in)) {
                        $record->attendance_request_id = $partialDispensa->id;
                        $record->expected_check_in = config('rh.dispensa.work_start', '08:00');
                        $record->expected_check_out = Dispensa::expectedCheckoutWithBreastfeeding($record->check_in);
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

    /**
     * Lista funcionários activos para a selecção no registo de ponto,
     * identificando os que estão de férias na data indicada.
     */
    public function listEmployeesForPoint(?string $date = null, ?int $departmentId = null): array
    {
        $date = $date ? Carbon::parse($date)->format('Y-m-d') : now()->toDateString();

        $query = Employee::where('status', 'active');

        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->orderBy('full_name')->with('department')->get(['id', 'employee_number', 'full_name']);

        $onLeave = LeaveRequest::query()
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get(['employee_id', 'start_date', 'end_date'])
            ->keyBy('employee_id');

        $onDispensa = \App\Models\RH\Attendance\AttendanceRequest::query()
            ->where('status', 'approved')
            ->where('benefit_active', true)
            ->where('applies_full_day', true)
            ->whereDate('start_date', '<=', $date)
            ->whereDate('end_date', '>=', $date)
            ->get(['id', 'employee_id', 'request_number', 'start_date', 'end_date', 'reason'])
            ->keyBy('employee_id');

        return $employees
            ->reject(fn (Employee $employee) => PontoExceptions::isEmployeeExempt($employee))
            ->map(function (Employee $employee) use ($onLeave, $onDispensa) {
                $leave = $onLeave->get($employee->id);
                $dispensa = $onDispensa->get($employee->id);

                if ($dispensa) {
                    return [
                        'id' => $employee->id,
                        'employee_number' => $employee->employee_number,
                        'full_name' => $employee->full_name,
                        'display_name' => "{$employee->full_name} — Dispensa aprovada",
                        'on_leave' => false,
                        'on_dispensa' => true,
                        'blocked' => true,
                        'dispensa_request_number' => $dispensa->request_number,
                        'dispensa_start_date' => $dispensa->start_date?->format('Y-m-d'),
                        'dispensa_end_date' => $dispensa->end_date?->format('Y-m-d'),
                        'message' => "Funcionário com dispensa aprovada de {$dispensa->start_date->format('d/m/Y')} a {$dispensa->end_date->format('d/m/Y')}: não regista ponto.",
                    ];
                }

                return [
                    'id' => $employee->id,
                    'employee_number' => $employee->employee_number,
                    'full_name' => $employee->full_name,
                    'display_name' => $leave ? "{$employee->full_name} — De férias" : $employee->full_name,
                    'on_leave' => (bool) $leave,
                    'on_dispensa' => false,
                    'blocked' => (bool) $leave,
                    'leave_start_date' => $leave?->start_date?->format('Y-m-d'),
                    'leave_end_date' => $leave?->end_date?->format('Y-m-d'),
                    'message' => $leave
                        ? "Funcionário de férias de {$leave->start_date->format('d/m/Y')} a {$leave->end_date->format('d/m/Y')}."
                        : null,
                ];
            })->values()->all();
    }

    /**
     * Listagem de pontualidade e assiduidade (módulo).
     *
     * - Padrão: registos do dia actual (referência do módulo).
     * - Filtros rápidos por dia: period=today|yesterday|day_before_yesterday ou date (outra data).
     * - Filtros por período: period=this_week|last_week|this_month|last_month|last_3_months|
     *   last_6_months|this_year ou start_date+end_date (intervalo personalizado).
     * - Filtro por funcionário: employee_id.
     * Cada registo inclui o número do agente (employee_number).
     */
    public function attendanceListing(array $filters, ?int $paginate = null): array
    {
        [$start, $end] = $this->resolveDateRange($filters);

        $query = Attendance::query()
            ->with(['employee', 'dispensa.type'])
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()]);

        if (! empty($filters['employee_id'])) {
            $query->where('employee_id', (int) $filters['employee_id']);
        }

        $exemptDepartmentIds = PontoExceptions::exemptDepartmentIds();

        if ($exemptDepartmentIds) {
            $query->whereHas('employee', fn ($q) => $q->whereNotIn('department_id', $exemptDepartmentIds));
        }

        $query->orderBy('date', 'desc')->orderBy('employee_id')->orderBy('check_in');

        $records = $paginate ? $query->paginate($paginate) : $query->get();
        $records->transform($this->recordPresenter());

        return [
            'records' => $records,
            'summary' => $this->attendanceSummary($query, $start, $end),
            'filters' => [
                'period' => $filters['period'] ?? 'today',
                'date' => $filters['date'] ?? null,
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'employee_id' => $filters['employee_id'] ?? null,
            ],
            'periods' => self::PERIODS,
        ];
    }

    /**
     * Assiduidade de um funcionário num período.
     *
     * Period de consulta: 1_day, 3_days, 1_week, 1_month, 3_months, 6_months, 1_year
     * (referência para hoje). Também aceita date ou start_date+end_date.
     */
    public function employeeAssiduidade(int $employeeId, array $filters): array
    {
        $employee = Employee::with('department')->findOrFail($employeeId);

        [$start, $end] = $this->resolveEmployeePeriodRange($filters);

        $exempt = PontoExceptions::isEmployeeExempt($employee);

        $recordQuery = Attendance::query()
            ->with('dispensa.type')
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->orderBy('date')
            ->orderBy('check_in');

        $records = $exempt ? collect() : $recordQuery->get()->map($this->recordPresenter());

        return [
            'employee' => [
                'id' => $employee->id,
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
            ],
            'exempt_from_ponto' => $exempt,
            'period' => [
                'period' => $filters['period'] ?? '1_day',
                'start_date' => $start->toDateString(),
                'end_date' => $end->toDateString(),
                'days_in_period' => (int) $start->diffInDays($end) + 1,
                'working_days' => $this->countWorkingDays($start, $end),
            ],
            'records' => $records,
            'summary' => $exempt
                ? null
                : array_merge(
                    $this->attendanceSummary($recordQuery, $start, $end),
                    ['on_leave_days' => $this->countOnLeaveDaysInPeriod($employeeId, $start, $end)]
                ),
            'employee_periods' => self::EMPLOYEE_PERIODS,
        ];
    }

    private function attendanceSummary($query, Carbon $start, Carbon $end): array
    {
        $total = (clone $query)->count();
        $present = (clone $query)->where('status', 'present')->count();
        $late = (clone $query)->where('status', 'late')->count();
        $absent = (clone $query)->where('status', 'absent')->count();
        $dispensado = (clone $query)->where('status', 'dispensado')->count();
        $justified = (clone $query)->where('status', 'absent')->where('is_justified', true)->count();

        return [
            'total_records' => $total,
            'present' => $present,
            'late' => $late,
            'absent' => $absent,
            'dispensado' => $dispensado,
            'justified_absences' => $justified,
            'unjustified_absences' => max($absent - $justified, 0),
            'total_hours_worked' => round((float) (clone $query)->sum('hours_worked'), 2),
            'employees_count' => (clone $query)->distinct('employee_id')->count('employee_id'),
            'days_in_period' => (int) $start->diffInDays($end) + 1,
            'working_days' => $this->countWorkingDays($start, $end),
        ];
    }

    private function recordPresenter(): \Closure
    {
        return function (Attendance $record) {
            return [
                'id' => $record->id,
                'employee_id' => $record->employee_id,
                'employee_number' => $record->employee?->employee_number,
                'employee_name' => $record->employee?->full_name,
                'department_id' => $record->employee?->department_id,
                'date' => $record->date?->toDateString(),
                'check_in' => $record->check_in,
                'check_out' => $record->check_out,
                'hours_worked' => (float) $record->hours_worked,
                'late_minutes' => $record->late_minutes,
                'overtime_minutes' => $record->overtime_minutes,
                'status' => $record->status,
                'absence_type' => $record->absence_type,
                'absence_reason' => $record->absence_reason,
                'is_justified' => (bool) $record->is_justified,
                'notes' => $record->notes,
                'shift_id' => $record->shift_id,
                'expected_check_in' => $record->expected_check_in,
                'expected_check_out' => $record->expected_check_out,
                'attendance_request_id' => $record->attendance_request_id,
                'dispensa' => $record->dispensa ? [
                    'id' => $record->dispensa->id,
                    'request_number' => $record->dispensa->request_number,
                    'type_name' => $record->dispensa->type?->name,
                    'start_date' => $record->dispensa->start_date?->toDateString(),
                    'end_date' => $record->dispensa->end_date?->toDateString(),
                    'reason' => $record->dispensa->reason,
                ] : null,
                'created_at' => $record->created_at?->toDateTimeString(),
                'updated_at' => $record->updated_at?->toDateTimeString(),
            ];
        };
    }

    private function resolveDateRange(array $filters): array
    {
        if (! empty($filters['date'])) {
            return $this->rangeForSingleDay($this->parseDate($filters['date']));
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $start = $this->parseDate($filters['start_date'])->startOfDay();
            $end = $this->parseDate($filters['end_date'])->endOfDay();

            if ($start > $end) {
                throw new \InvalidArgumentException('A data inicial não pode ser posterior à data final.');
            }

            return [$start, $end];
        }

        $period = $filters['period'] ?? 'today';
        $now = now();

        $ranges = [
            'yesterday' => fn () => [$now->copy()->subDay(), $now->copy()->subDay()],
            'day_before_yesterday' => fn () => [$now->copy()->subDays(2), $now->copy()->subDays(2)],
            'this_week' => fn () => [$now->copy()->startOfWeek(), $now->copy()->endOfWeek()],
            'last_week' => fn () => [$now->copy()->subWeek()->startOfWeek(), $now->copy()->startOfWeek()->subDay()],
            'this_month' => fn () => [$now->copy()->startOfMonth(), $now->copy()->endOfMonth()],
            'last_month' => fn () => [$now->copy()->subMonthNoOverflow()->startOfMonth(), $now->copy()->subMonthNoOverflow()->endOfMonth()],
            'last_3_months' => fn () => [$now->copy()->subMonthsNoOverflow(3), $now->copy()],
            'last_6_months' => fn () => [$now->copy()->subMonthsNoOverflow(6), $now->copy()],
            'this_year' => fn () => [$now->copy()->startOfYear(), $now->copy()->endOfYear()],
        ];

        if (isset($ranges[$period])) {
            [$start, $end] = $ranges[$period]();

            return [$start->startOfDay(), $end->endOfDay()];
        }

        return $this->rangeForSingleDay($now);
    }

    private function resolveEmployeePeriodRange(array $filters): array
    {
        if (! empty($filters['date'])) {
            return $this->rangeForSingleDay($this->parseDate($filters['date']));
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            $start = $this->parseDate($filters['start_date'])->startOfDay();
            $end = $this->parseDate($filters['end_date'])->endOfDay();

            if ($start > $end) {
                throw new \InvalidArgumentException('A data inicial não pode ser posterior à data final.');
            }

            return [$start, $end];
        }

        $period = $filters['period'] ?? '1_day';
        $now = now();

        $starts = [
            '1_day' => $now->copy()->startOfDay(),
            '3_days' => $now->copy()->subDays(2)->startOfDay(),
            '1_week' => $now->copy()->subDays(6)->startOfDay(),
            '1_month' => $now->copy()->subMonthNoOverflow()->startOfDay(),
            '3_months' => $now->copy()->subMonthsNoOverflow(3)->startOfDay(),
            '6_months' => $now->copy()->subMonthsNoOverflow(6)->startOfDay(),
            '1_year' => $now->copy()->subYear()->startOfDay(),
        ];

        return [
            $starts[$period] ?? $now->copy()->startOfDay(),
            $now->copy()->endOfDay(),
        ];
    }

    private function rangeForSingleDay(Carbon $date): array
    {
        return [$date->copy()->startOfDay(), $date->copy()->endOfDay()];
    }

    private function parseDate(mixed $value): Carbon
    {
        if (! $value) {
            throw new \InvalidArgumentException('Data inválida.');
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            throw new \InvalidArgumentException('Data inválida.');
        }
    }

    private function countWorkingDays(Carbon $start, Carbon $end): int
    {
        $day = $start->copy()->startOfDay();
        $limit = $end->copy()->endOfDay();
        $count = 0;

        while ($day->lte($limit)) {
            if (! $day->isWeekend() && ! ($this->holidayService?->isHoliday($day) ?? false)) {
                $count++;
            }
            $day->addDay();
        }

        return $count;
    }

    private function countOnLeaveDaysInPeriod(int $employeeId, Carbon $start, Carbon $end): int
    {
        $leaves = LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('status', 'approved')
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get(['start_date', 'end_date']);

        $days = 0;
        foreach ($leaves as $leave) {
            $from = max($leave->start_date->startOfDay(), $start->startOfDay());
            $to = min($leave->end_date->endOfDay(), $end->endOfDay());
            $days += $from->diffInDays($to) + 1;
        }

        return $days;
    }
}
