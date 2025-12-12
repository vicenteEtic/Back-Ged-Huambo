<?php

namespace App\Http\Controllers\Entities;

use App\Http\Controllers\AbstractController;
use App\Http\Requests\Entities\RiskAssessmentFindDateRequest;
use App\Services\Entities\RiskAssessmentService;
use App\Http\Requests\Entities\RiskAssessmentRequest;
use App\Jobs\GenerateAlertsJob;
use Exception;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Stmt\Return_;

use function PHPUnit\Framework\returnSelf;

class RiskAssessmentController extends AbstractController
{
    protected ?string $logType = 'entity';
    protected ?string $nameEntity = "Avaliação de Risco";
    protected ?string $fieldName = "entity?->social_denomination";

    public function __construct(RiskAssessmentService $service)
    {
        $this->service = $service;
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RiskAssessmentRequest $request)
    {
        try {

            DB::beginTransaction();

            $this->logRequest();
            $riskAssessment = $this->service->store($request->validated());

            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                customMessage: "{$riskAssessment?->entity?->social_denomination} Foi realizada uma avaliação  que resultou em uma pontuação de {$riskAssessment->score} com um nível de risco {$riskAssessment->risk_level} e o tipo de diligência {$riskAssessment->diligence}.",
                idEntity: $riskAssessment->entity_id
            );

            $userName = auth()->user()?->first_name ?? 'Usuário desconhecido';
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "{$userName} realizou uma avaliação  na entidade  {$riskAssessment?->entity?->social_denomination} que resultou em uma pontuação de {$riskAssessment->score} com um nível de risco {$riskAssessment->risk_level} e o tipo de diligência {$riskAssessment->diligence}.",
                idEntity: null
            );
            DB::commit();
            return response()->json($riskAssessment, Response::HTTP_CREATED);
        } catch (Exception $e) {
            DB::rollBack();
            $this->logRequest($e);
            $this->logToDatabase(
                type: 'entity',
                level: 'error',
                customMessage: "O usuário " . auth()->user()->first_name . " tentou criar uma avaliação de risco, mas ocorreu um erro.",
                idEntity: $request->entity_id
            );
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByCategory(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByCategory($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByProfession(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByProfession($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByChannel(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByChannel($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByPep(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByPep($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByCountryResidence(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByCountryResidence($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByNationality(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByNationality($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getTotalRiskLevelByProductRisk(RiskAssessmentFindDateRequest $request)
    {
        try {
            $this->logRequest();
            $result = $this->service->getTotalRiskLevelByProductRisk($request->validated());
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function getHeatMap(int $year = null)
    {
        try {
            $this->logRequest();
            $result = $this->service->getHeatMap($year);
            return response()->json($result, Response::HTTP_OK);
        } catch (ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], Response::HTTP_NOT_FOUND);
        } catch (Exception $e) {
            $this->logRequest($e);
            return response()->json($e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
