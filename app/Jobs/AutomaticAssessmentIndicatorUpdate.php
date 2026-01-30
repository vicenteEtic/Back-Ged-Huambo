<?php

namespace App\Jobs;

use App\Models\Entities\RiskAssessment;
use App\Repositories\Entities\BeneficialOwnerRepository;
use App\Repositories\Entities\RiskAssessmentRepository;
use App\Repositories\Indicator\IndicatorTypeRepository;
use App\Services\Entities\ProductRiskService;
use App\Services\Entities\RiskAssessmentService;
use App\Services\Log\LogService;
use Illuminate\Bus\Queueable as BusQueueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class AutomaticAssessmentIndicatorUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private int $id_indicator;
    private int $user_id;
    

    public function __construct(int $id_indicator)
    {
        $this->id_indicator = $id_indicator;
    
    }

    public function handle(
        RiskAssessmentService $riskAssessmentService,
        RiskAssessmentRepository $riskAssessmentRepository,
        ProductRiskService $productRiskService,
        BeneficialOwnerRepository $beneficialOwnerRepository,
        LogService $logService,
        IndicatorTypeRepository $indicatorTypeRepository



    ): void {

        Log::info('Job AutomaticAssessmentIndicatorUpdate iniciado.', [
            'id_indicator' => $this->id_indicator
        ]);

        try {
            $indicators = [
                'identification_capacity',
                'form_establishment',
                'profession',
                'country_residence',
                'nationality',
                'channel',
            ];

            foreach ($indicators as $indicatorType) {
                $assessments = $riskAssessmentRepository->findByIndicatorType(
                    $indicatorType,
                    $this->id_indicator
                );

                Log::info('Avaliações encontradas.', [
                    'indicator' => $indicatorType,
                    'count' => $assessments->count(),
                ]);

                foreach ($assessments as $assessment) {
          $this->processAssessment(
    $assessment,
    $indicatorType,
    $productRiskService,
    $beneficialOwnerRepository,
    $riskAssessmentService,
    $logService,
    $indicatorTypeRepository,
    $this->id_indicator
);
                }
            }
        } catch (\Throwable $e) {
            Log::error('Erro no job AutomaticAssessmentIndicatorUpdate.', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'indicator' => $this->id_indicator
            ]);

            throw $e;
        }


        Log::info('Job AutomaticAssessmentIndicatorUpdate finalizado.', [
            'id_indicator' => $this->id_indicator
        ]);
    }

    private function processAssessment(
     RiskAssessment $assessment,
    string $indicatorType,
    ProductRiskService $productRiskService,
    BeneficialOwnerRepository $beneficialOwnerRepository,
    RiskAssessmentService $riskAssessmentService,
    LogService $logService,
    IndicatorTypeRepository $indicatorTypeRepository,
    int $id_indicator
    ): void {

        Log::info('Processando assessment.', [
            'assessment_id' => $assessment->id,
            'entity_id' => $assessment->entity_id
        ]);

        $products = $productRiskService->showProduct($assessment->id);
        $beneficialOwners = $beneficialOwnerRepository->showBeneficialOwner($assessment->id);

        $indicatorTypeModel = $indicatorTypeRepository->getByDescriptionAll($id_indicator);

        if (!$indicatorTypeModel) {
            Log::warning('IndicatorType não encontrado.', [
                'id_indicator' => $id_indicator
            ]);
            return;
        }

        $data = [
            'product_risk' => $products ? $products->pluck('product_id')->toArray() : [],
            'beneficial_owners' => $beneficialOwners ? $beneficialOwners->toArray() : [],
            'entity_id' => $assessment->entity_id,
            'risk_assessment' => $assessment,
            'type_assessment' => 3,
        ];

        $data[$indicatorType] = $assessment->{$indicatorType};

        $data = array_merge($assessment->toArray(), $data);

        unset($data['id'], $data['created_at'], $data['updated_at']);

      
      $assmentData=  $riskAssessmentService->store($data);

     $customMessage = "Avaliação automática devido à alteração do indicador, resultando em uma pontuação de {$assmentData->score}, com nível de risco {$assmentData->risk_level} e tipo de diligência {$assmentData->diligence}.";

        $logService->storeLog(
            'info',
            'edit',
            'system',
            'risk_assessment',
            $assessment->entity_id,
            null,
            $customMessage
        );

        Log::info('Store executado com sucesso.', [
            'assessment_id' => $assessment->id
        ]);
    }
}
