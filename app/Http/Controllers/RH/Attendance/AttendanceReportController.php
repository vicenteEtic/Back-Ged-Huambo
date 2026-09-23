<?php

namespace App\Http\Controllers\RH\Attendance;

use App\Services\RH\Attendance\AttendanceReportService;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AttendanceReportController
{
    public function __construct(
        protected AttendanceReportService $report
    ) {}

    /**
     * Dados do relatório (pré-visualização no frontend) com filtro completo.
     */
    public function data(Request $request, ?int $employeeId = null)
    {
        try {
            $filters = $request->only(['date', 'period', 'start_date', 'end_date', 'employee_id']);

            return response()->json($this->report->data(
                $filters,
                $employeeId ?: ($filters['employee_id'] ?? null)
            ));
        } catch (Exception $e) {
            Log::error('Erro ao gerar dados do relatório de assiduidade', ['message' => $e->getMessage()]);

            return response()->json(['error' => $e->getMessage()], Response::HTTP_UNPROCESSABLE_ENTITY);
        }
    }

    /**
     * Gera e descarrega o relatório PDF (padrão Governo).
     */
    public function download(Request $request, ?int $employeeId = null): BinaryFileResponse
    {
        $filters = $request->only(['date', 'period', 'start_date', 'end_date', 'employee_id']);
        $filters = array_filter($filters);

        $targetEmployeeId = $employeeId ?: ($filters['employee_id'] ?? null) ?: null;

        $pdf = $this->report->render($filters, $targetEmployeeId, auth()->user());

        $tmp = tempnam(sys_get_temp_dir(), 'rel_assid_');
        file_put_contents($tmp, $pdf);

        return response()
            ->download($tmp, $this->report->fileName($filters, $targetEmployeeId), ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    /**
     * Imprime o mapa mensal de efectividade do pessoal.
     * Aceita month=1..12, year=YYYY, department_id e gabinete_id.
     */
    public function effectivenessMap(Request $request): BinaryFileResponse
    {
        $monthValue = (string) $request->query('month', '');
        $year = (int) $request->query('year', now()->year);

        // Também aceita month=YYYY-MM para facilitar o uso por campos HTML month.
        if (preg_match('/^(\d{4})-(\d{1,2})$/', $monthValue, $matches)) {
            $year = (int) $matches[1];
            $monthValue = $matches[2];
        }

        $month = (int) $monthValue;
        $departmentId = $request->integer('department_id') ?: null;
        $gabineteId = $request->integer('gabinete_id') ?: ($request->integer('gabinete') ?: null);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            abort(422, 'O mês deve estar entre 1 e 12 e o ano deve ser válido.');
        }

        $pdf = $this->report->renderEffectivenessMap($year, $month, $departmentId, $gabineteId, auth()->user());
        $tmp = tempnam(sys_get_temp_dir(), 'mapa_efectividade_');
        file_put_contents($tmp, $pdf);

        return response()
            ->download($tmp, $this->report->effectivenessMapFileName($year, $month), ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }

    public function effectivenessMapExcel(Request $request): BinaryFileResponse
    {
        $monthValue = (string) $request->query('month', '');
        $year = (int) $request->query('year', now()->year);

        if (preg_match('/^(\d{4})-(\d{1,2})$/', $monthValue, $matches)) {
            $year = (int) $matches[1];
            $monthValue = $matches[2];
        }

        $month = (int) $monthValue;
        $departmentId = $request->integer('department_id') ?: null;
        $gabineteId = $request->integer('gabinete_id') ?: ($request->integer('gabinete') ?: null);

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            abort(422, 'O mês deve estar entre 1 e 12 e o ano deve ser válido.');
        }

        $content = $this->report->renderEffectivenessMapExcel($year, $month, $departmentId, $gabineteId);
        $tmp = tempnam(sys_get_temp_dir(), 'mapa_efectividade_excel_');
        file_put_contents($tmp, $content);

        return response()
            ->download($tmp, $this->report->effectivenessMapExcelFileName($year, $month), ['Content-Type' => 'application/vnd.ms-excel'])
            ->deleteFileAfterSend(true);
    }
}
