<?php

namespace App\Http\Controllers\RH\Payroll;

use App\Http\Controllers\Controller;
use App\Models\RH\Payroll\Payslip;
use App\Services\RH\Payroll\PayslipService;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
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
            Log::error('Error listing payslips', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function generate(int $periodId)
    {
        try {
            $count = $this->payslipService->generateForPeriod($periodId);
            return response()->json(['message' => "{$count} título(s) gerado(s).", 'count' => $count]);
        } catch (Exception $e) {
            Log::error('Error generating payslips', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function byEmployee(int $employeeId)
    {
        try {
            return response()->json($this->payslipService->historyByEmployee($employeeId));
        } catch (Exception $e) {
            Log::error('Error fetching payslips', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function show(int $id)
    {
        try {
            return response()->json(Payslip::with(['employee', 'period'])->findOrFail($id));
        } catch (ModelNotFoundException $e) {
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            Log::error('Error fetching payslip', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Internal server error.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
