<?php

namespace App\Services\RH\Attendance;

use App\Models\RH\Employee\Employee;
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
