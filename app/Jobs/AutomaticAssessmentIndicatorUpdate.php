<?php

namespace App\Jobs;

use App\Models\Entities\RiskAssessment;
use App\Repositories\Entities\BeneficialOwnerRepository;
use App\Repositories\Entities\BeneficialRepository;
use App\Repositories\Entities\RiskAssessmentRepository;
use App\Repositories\Indicator\IndicatorTypeRepository;
use App\Services\Entities\ProductRiskService;
use App\Services\Entities\RiskAssessmentService;
use App\Services\Log\LogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AutomaticAssessmentIndicatorUpdate implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    // Correção: define número de tentativas e timeout
    public int $tries = 5;       // tenta 5 vezes
    public int $timeout = 120;   // 2 minutos por job

    private int $id_indicator;

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
        IndicatorTypeRepository $indicatorTypeRepository,
        BeneficialRepository $beneficialRepository
    ): void {
        Log::info('Job AutomaticAssessmentIndicatorUpdate iniciado.', [
            'id_indicator' => $this->id_indicator
        ]);

        $indicators = [
            'identification_capacity',
            'form_establishment',
            'professionP',
            'categoryP',
            'country_residence',
            'nationality',
            'channel',
        ];

        foreach ($indicators as $indicatorType) {
            try {
                if (!Schema::hasColumn('risk_assessment', $indicatorType)) {
                    Log::warning("Indicador {$indicatorType} não existe na tabela risk_assessment, pulando...");
                    continue;
                }

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
                        $beneficialRepository
                    );
                }

            } catch (\Throwable $e) {
                // Log do erro e relança para o Laravel controlar retry
                Log::error("Erro ao processar indicador {$indicatorType}.", [
                    'message' => $e->getMessage(),
                    'line' => $e->getLine(),
                    'file' => $e->getFile(),
                    'id_indicator' => $this->id_indicator
                ]);
                throw $e;
            }
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
        BeneficialRepository $beneficialRepository
    ): void {
        Log::info('Processando assessment.', [
            'assessment_id' => $assessment->id,
            'entity_id' => $assessment->entity_id,
            'indicator' => $indicatorType
        ]);

        try {
            $products = $productRiskService->showProduct($assessment->id);
            $productsArray = $products ? $products->pluck('product_id')->toArray() : [];

            $beneficialOwners = $beneficialOwnerRepository->showBeneficialOwner($assessment->id);
            $beneficialOwnersArray = $beneficialOwners ? $beneficialOwners->toArray() : [];

            $beneficial = $beneficialRepository->showBeneficial($assessment->id);
            $beneficialArray = $beneficial ? $beneficial->toArray() : [];

            $indicatorTypeModel = $indicatorTypeRepository->getByDescriptionAll($this->id_indicator);

            if (!$indicatorTypeModel) {
                Log::warning('IndicatorType não encontrado.', [
                    'id_indicator' => $this->id_indicator
                ]);
                return;
            }

            $data = [
                'product_risk' => $productsArray,
                'beneficial_owners' => $beneficialOwnersArray,
                'beneficial' => $beneficialArray,
                'entity_id' => $assessment->entity_id,
                'risk_assessment' => $assessment,
                'type_assessment' => 3,
                $indicatorType => $assessment->{$indicatorType} ?? null
            ];

            $data = array_merge($assessment->toArray(), $data);
            unset($data['id'], $data['created_at'], $data['updated_at']);

            $storedAssessment = $riskAssessmentService->store($data);

            $score = $storedAssessment->score ?? 'null';
            $riskLevel = $storedAssessment->risk_level ?? 'null';
            $diligence = $storedAssessment->diligence ?? 'null';

            $customMessage = "Avaliação automática devido à alteração do indicador '{$indicatorTypeModel->description}', "
                . "pontuação {$score}, nível de risco {$riskLevel}, tipo de diligência {$diligence}.";

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

        } catch (\Throwable $e) {
            Log::error('Erro ao processar/armazenar assessment.', [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
                'assessment_id' => $assessment->id
            ]);
            throw $e; // relança para Laravel controlar retry
        }
    }
}