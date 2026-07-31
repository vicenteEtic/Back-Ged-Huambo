<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\Controller;
use App\Models\RH\Payroll\Payslip;
use App\Services\RH\Payroll\PayslipService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayslipController extends Controller
{
    public function __construct(protected PayslipService $payslipService) {}

    public function index()
    {
        try {
            return response()->json(Payslip::with(['employee:id,full_name', 'period:id,name'])->orderByDesc('created_at')->paginate(request('paginate', 50)));
        } catch (Exception $e) {
            Log::error('Erro ao listar títulos de vencimento', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generate(Request $request, int $periodId)
    {
        $request->validate([
            'employee_ids' => 'required|array',
            'employee_ids.*' => 'integer|exists:employees,id',
        ]);

        try {
            $count = $this->payslipService->generateForPeriod($periodId, $request->input('employee_ids'));
            return response()->json(['message' => "{$count} título(s) gerado(s).", 'count' => $count]);
        } catch (Exception $e) {
            Log::error('Erro ao gerar títulos de vencimento', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byEmployee(int $employeeId)
    {
        try {
            return response()->json($this->payslipService->historyByEmployee($employeeId));
        } catch (Exception $e) {
            Log::error('Erro ao buscar títulos de vencimento', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id)
    {
        try {
            return response()->json(Payslip::with(['employee', 'period'])->findOrFail($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Recurso não encontrado.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Erro ao buscar título de vencimento', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
