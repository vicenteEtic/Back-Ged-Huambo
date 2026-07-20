<?php

namespace App\Http\Controllers\RH\Reports;

use App\Http\Controllers\Controller;
use App\Services\RH\Reports\DashboardService;
use Exception;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function __construct(protected DashboardService $dashboardService) {}

    public function overview()
    {
        try {
            return response()->json($this->dashboardService->overview());
        } catch (Exception $e) {
            Log::error('Dashboard overview error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function monthlyBirthdays()
    {
        try {
            return response()->json($this->dashboardService->monthlyBirthdays());
        } catch (Exception $e) {
            Log::error('Dashboard birthdays error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function leaveSummary()
    {
        try {
            return response()->json($this->dashboardService->leaveSummary(request('year')));
        } catch (Exception $e) {
            Log::error('Dashboard leave summary error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function attendanceSummary()
    {
        try {
            return response()->json($this->dashboardService->attendanceSummary(request('year'), request('month')));
        } catch (Exception $e) {
            Log::error('Dashboard attendance summary error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function documentExpiryAlert()
    {
        try {
            return response()->json($this->dashboardService->documentExpiryAlert((int) request('days', 30)));
        } catch (Exception $e) {
            Log::error('Dashboard document expiry error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function turnover()
    {
        try {
            return response()->json($this->dashboardService->turnover(request('year')));
        } catch (Exception $e) {
            Log::error('Dashboard turnover error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function salaryEvolution()
    {
        try {
            return response()->json($this->dashboardService->salaryEvolutionByDepartment());
        } catch (Exception $e) {
            Log::error('Dashboard salary evolution error', ['message' => $e->getMessage()]);
            return response()->json(['error' => 'Erro interno no servidor.'], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
