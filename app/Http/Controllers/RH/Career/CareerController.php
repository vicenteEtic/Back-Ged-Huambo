<?php

namespace App\Http\Controllers\RH\Career;

use App\Http\Controllers\Controller;
use App\Models\RH\Employee\Employee;
use App\Services\RH\Career\CareerService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class CareerController extends Controller
{
    public function __construct(
        protected CareerService $careerService
    ) {}

    public function show(int $employeeId)
    {
        try {
            $employee = Employee::with(['position', 'department', 'functionalHistory'])
                ->findOrFail($employeeId);

            return response()->json($this->careerService->calculate($employee));
        } catch (Exception $e) {
            Log::error('Erro ao consultar carreira', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function index()
    {
        try {
            $filters = request()->only(['status', 'department_id']);
            return response()->json($this->careerService->calculateForAll($filters));
        } catch (Exception $e) {
            Log::error('Erro ao consultar carreiras', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
