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
     * Aceita month=1..12 e year=YYYY; o ano actual é usado quando omitido.
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

        if ($month < 1 || $month > 12 || $year < 2000 || $year > 2100) {
            abort(422, 'O mês deve estar entre 1 e 12 e o ano deve ser válido.');
        }

        $pdf = $this->report->renderEffectivenessMap($year, $month, auth()->user());
        $tmp = tempnam(sys_get_temp_dir(), 'mapa_efectividade_');
        file_put_contents($tmp, $pdf);

        return response()
            ->download($tmp, $this->report->effectivenessMapFileName($year, $month), ['Content-Type' => 'application/pdf'])
            ->deleteFileAfterSend(true);
    }
}
