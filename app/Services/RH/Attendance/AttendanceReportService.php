<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Employee\Employee;
use App\Models\RH\Attendance\Attendance;
use App\Models\RH\Leave\Holiday;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;

class AttendanceReportService
{
    public function __construct(
        protected AttendanceService $attendanceService
    ) {}

    /**
     * Dados do relatório respeitando o sistema de filtros da listagem
     * (period/date/start_date+end_date/employee_id).
     */
    public function data(array $filters, ?int $employeeId = null): array
    {
        if ($employeeId) {
            $raw = $this->attendanceService->employeeAssiduidade($employeeId, $filters);

            return [
                'employee' => $raw['employee'],
                'records' => $raw['records'],
                'summary' => $raw['summary'] ?? $this->emptySummary(),
                'filters' => [
                    'period' => $raw['period']['period'],
                    'start_date' => $raw['period']['start_date'],
                    'end_date' => $raw['period']['end_date'],
                ],
            ];
        }

        return $this->attendanceService->attendanceListing($filters);
    }

    public function render(array $filters, ?int $employeeId = null, ?User $generatedBy = null): string
    {
        $filters = array_merge(['period' => 'today'], $filters);
        $data = $this->data($filters, $employeeId);

        $employee = null;

        if ($employeeId) {
            $department = Employee::find($employeeId)?->department?->name;

            $employee = [
                'name' => $data['employee']['full_name'],
                'employee_number' => $data['employee']['employee_number'],
                'department' => $department,
            ];
        }

        $html = view('rh.attendance.report', [
            'records' => $data['records'],
            'listing' => [
                'summary' => $data['summary'],
                'filters' => $data['filters'],
            ],
            'periodLabel' => $this->periodLabel($filters),
            'employee' => $employee,
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $generatedBy?->name ?? ($generatedBy?->username ?? 'Sistema'),
            'appName' => config('app.name'),
        ])->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function fileName(array $filters, ?int $employeeId = null): string
    {
        $period = $filters['period'] ?? ($employeeId ? 'assiduidade' : 'periodo');

        return 'Relatorio_Pontualidade_Assiduidade_'.str_replace([' ', '/'], '_', strtoupper($period)).'.pdf';
    }

    /**
     * Prepara o mapa mensal de efectividade do pessoal.
     * Cada linha representa um funcionário activo, mesmo sem faltas registadas.
     */
    public function effectivenessMap(int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $workingDays = $this->workingDays($start, $end);

        $absenceCodes = [
            'unjustified' => ['injustificada', 'unjustified'],
            'article_65' => ['artigo_65', 'art65', 'lei_65'],
            'article_66' => ['artigo_66', 'art66', 'lei_66'],
            'article_67' => ['artigo_67', 'art67', 'lei_67'],
            'article_68' => ['artigo_68', 'art68', 'lei_68'],
            'sickness' => ['doenca', 'doença', 'licenca_doenca', 'licenca_medica'],
            'marriage' => ['casamento'],
            'childbirth' => ['parto', 'maternidade', 'paternidade'],
            'disciplinary' => ['disciplinar'],
            'registered' => ['registada', 'censura_registada'],
            'called' => ['chamada'],
        ];

        $records = Attendance::query()
            ->where('status', 'absent')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['employee_id', 'absence_type', 'is_justified']);

        $employees = Employee::query()
            ->with(['careerCategory', 'position', 'department'])
            ->where('status', 'active')
            ->whereNotIn('department_id', \App\Support\PontoExceptions::exemptDepartmentIds())
            ->orderBy('full_name')
            ->get();

        $rows = $employees->map(function (Employee $employee) use ($records, $absenceCodes, $workingDays) {
            $employeeRecords = $records->where('employee_id', $employee->id);
            $counts = array_fill_keys(array_keys($absenceCodes), 0);

            foreach ($employeeRecords as $record) {
                $code = strtolower((string) $record->absence_type);
                foreach ($absenceCodes as $column => $codes) {
                    if (in_array($code, $codes, true)) {
                        $counts[$column]++;
                        break;
                    }
                }

                if ((bool) $record->is_justified === false && ! in_array($code, $absenceCodes['unjustified'], true)) {
                    $counts['unjustified']++;
                }
            }

            $totalAbsences = $employeeRecords->count();

            return array_merge([
                'employee_number' => $employee->employee_number,
                'full_name' => $employee->full_name,
                'category' => $employee->careerCategory?->name ?? $employee->position?->name ?? '-',
                'department' => $employee->department?->name ?? '-',
            ], $counts, [
                'total_absences' => $totalAbsences,
                'effective_days' => max($workingDays - $totalAbsences, 0),
            ]);
        })->values();

        return [
            'year' => $year,
            'month' => $month,
            'month_name' => $this->monthName($month),
            'month_end' => $end->format('d'),
            'working_days' => $workingDays,
            'rows' => $rows,
        ];
    }

    public function renderEffectivenessMap(int $year, int $month, ?User $generatedBy = null): string
    {
        $data = $this->effectivenessMap($year, $month);
        $html = view('rh.attendance.effectiveness-map', array_merge($data, [
            'generatedAt' => now()->format('d/m/Y H:i'),
            'generatedBy' => $generatedBy?->name ?? ($generatedBy?->username ?? 'Sistema'),
            'appName' => config('app.name'),
        ]))->render();

        $options = new Options;
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'Times');
        $options->set('isHtml5ParserEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A3', 'landscape');
        $dompdf->render();

        return $dompdf->output();
    }

    public function effectivenessMapFileName(int $year, int $month): string
    {
        return sprintf('Mapa_Efectividade_%02d-%d.pdf', $month, $year);
    }

    private function workingDays(Carbon $start, Carbon $end): int
    {
        $holidays = Holiday::query()
            ->where('is_active', true)
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                    ->orWhere('recurrent', true);
            })
            ->get(['date', 'recurrent'])
            ->filter(function (Holiday $holiday) use ($start) {
                $date = Carbon::parse($holiday->date);

                return $holiday->recurrent
                    ? $date->month === $start->month
                    : true;
            })
            ->map(function (Holiday $holiday) use ($start) {
                $date = Carbon::parse($holiday->date);

                return ($holiday->recurrent ? $start->year : $date->year).'-'.$date->format('m-d');
            })
            ->all();
        $days = 0;
        for ($day = $start->copy(); $day->lte($end); $day->addDay()) {
            $holidayKey = $day->year.'-'.$day->format('m-d');
            if (! $day->isWeekend() && ! in_array($holidayKey, $holidays, true)) {
                $days++;
            }
        }

        return $days;
    }

    private function monthName(int $month): string
    {
        return [1 => 'Janeiro', 2 => 'Fevereiro', 3 => 'Março', 4 => 'Abril', 5 => 'Maio', 6 => 'Junho', 7 => 'Julho', 8 => 'Agosto', 9 => 'Setembro', 10 => 'Outubro', 11 => 'Novembro', 12 => 'Dezembro'][$month];
    }

    protected function periodLabel(array $filters): string
    {
        $period = $filters['period'] ?? null;

        if ($period && isset(AttendanceService::PERIODS[$period])) {
            return AttendanceService::PERIODS[$period];
        }

        if (! empty($filters['date'])) {
            return 'Dia '.Carbon::parse($filters['date'])->format('d/m/Y');
        }

        if (! empty($filters['start_date']) && ! empty($filters['end_date'])) {
            return Carbon::parse($filters['start_date'])->format('d/m/Y').' a '.Carbon::parse($filters['end_date'])->format('d/m/Y');
        }

        return 'Período seleccionado';
    }

    protected function emptySummary(): array
    {
        return [
            'total_records' => 0,
            'present' => 0,
            'late' => 0,
            'absent' => 0,
            'dispensado' => 0,
            'justified_absences' => 0,
            'unjustified_absences' => 0,
            'total_hours_worked' => 0,
            'employees_count' => 0,
            'days_in_period' => 0,
            'working_days' => 0,
        ];
    }
}
