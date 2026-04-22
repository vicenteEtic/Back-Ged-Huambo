<?php

namespace App\Http\Controllers\Dashboard;

use Exception; 
use Illuminate\Support\Facades\Log;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Http\Requests\Entities\RiskAssessmentFindDateRequest;
use App\Services\Dashboard\DashboardService;

class DashboardController extends Controller
{
    protected ?string $logType = 'dashboard';
    protected ?string $nameEntity = "Dashboard";

    public function __construct(private DashboardService $service) {}



  

    public function dashboard(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $data = $this->service->totalGeralData($request->validated());
            $this->logToDatabase(
                type: $this->logType,
                level: 'info',
                customMessage: "Acessou o dashboard geral.",
            );
            return response()->json($data, Response::HTTP_OK);
        } catch (Exception $e) {
            $this->logRequest($e);
             Log::error('Erro interno', [
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);

    return response()->json([
        'error' => 'Erro interno no servidor.'
    ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
