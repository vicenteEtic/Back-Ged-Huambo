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
}
