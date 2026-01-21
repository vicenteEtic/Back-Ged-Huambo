<?php

namespace App\Jobs;

use App\Repositories\Entities\BeneficialOwnerRepository;
use App\Repositories\Entities\RiskAssessmentRepository;
use App\Services\Entities\ProductRiskService;
use App\Services\Entities\RiskAssessmentService;
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

    public function __construct(int $id_indicator)
    {
        $this->id_indicator = $id_indicator;
    }

    public function handle(
        RiskAssessmentService $riskAssessmentService,
        RiskAssessmentRepository $riskAssessmentRepository,
        ProductRiskService $productRiskService,

        BeneficialOwnerRepository $beneficialOwnerRepository


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
                $assessments = $riskAssessmentRepository->findByIndicatoryType(
                    $indicatorType,
                    $this->id_indicator
                );
        
                Log::info('Avaliações encontradas.', [
                    'indicator' => $indicatorType,
                    'count' => $assessments->count(),
                ]);
        
                foreach ($assessments as $assessment) {
                    $this->processAssessment($assessment, $indicatorType, $productRiskService, $beneficialOwnerRepository, $riskAssessmentService);
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
        $assessment,
        string $indicatorType,
        $productRiskService,
        $beneficialOwnerRepository,
        $riskAssessmentService
    ) {
        Log::info('Processando assessment.', [
            'assessment_id' => $assessment->id,
            'entity_id' => $assessment->entity_id
        ]);
    
        $products = $productRiskService->showProduct($assessment->id);
        $beneficialOwners = $beneficialOwnerRepository->showBeneficialOwner($assessment->id);
    
        Log::info('Produtos retornados.', [
            'assessment_id' => $assessment->id,
            'products_count' => $products ? $products->count() : 0,
            'beneficial_owners' => $beneficialOwners ? $beneficialOwners->count() : 0,
        ]);
    
        $data = [
            'product_risk' => $products ? $products->pluck('product_id')->toArray() : [],
            'beneficial_owners' => $beneficialOwners ? $beneficialOwners->toArray() : [],
            'entity_id' => $assessment->entity_id,
            'risk_assessment' => $assessment,
            'type_assessment' => 3,
        ];
    
        // Adiciona o valor do indicador ao data
        $data[$indicatorType] = $assessment->{$indicatorType};
    
        // Merge com os dados do assessment
        $data = array_merge($assessment->toArray(), $data);
    
        unset($data['id'], $data['created_at'], $data['updated_at']);
    
        Log::info('Dados enviados para store.', [
            'assessment_id' => $assessment->id,
            'data_keys' => array_keys($data),
            'product_risk' => $data['product_risk']
        ]);
    
        $riskAssessmentService->store($data);
    
        Log::info('Store executado com sucesso.', [
            'assessment_id' => $assessment->id
        ]);
    }
    
}
