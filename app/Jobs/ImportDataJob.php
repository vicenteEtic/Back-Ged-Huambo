<?php

namespace App\Jobs;

use App\Enum\TypeAssessment;
use App\Enum\StatusAssessment;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use App\Repositories\Alert\AlertRepository;
use App\Services\Entities\EntitiesService;
use App\Services\Entities\RiskAssessmentService;
use App\Services\Indicator\IndicatorTypeService;
use App\Models\Entities\RiskAssessmentControl;
use App\Traits\DatabaseLogger;

class ImportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, DatabaseLogger;

    protected array $data;
    protected int $userID;
    protected int $batchId;

    protected RiskAssessmentService $riskAssessmentService;
    protected AlertRepository $alertRepository;
    protected IndicatorTypeService $indicatorService;
    protected EntitiesService $entityService;

    public $tries = 5;
    public $timeout = 36000;

    public function __construct(array $data, int $userID, int $batchId)
    {
        $this->data = $data;
        $this->userID = $userID;
        $this->batchId = $batchId;
    }

    public function handle(
        RiskAssessmentService $riskAssessmentService,
        AlertRepository $alertRepository,
        IndicatorTypeService $indicatorService,
        EntitiesService $entityService
    ) {

        $this->riskAssessmentService = $riskAssessmentService;
        $this->alertRepository = $alertRepository;
        $this->indicatorService = $indicatorService;
        $this->entityService = $entityService;

        Log::info('ImportDataJob iniciado', [
            'batchId' => $this->batchId,
            'total_records' => count($this->data)
        ]);

        Auth::loginUsingId($this->userID);

        foreach ($this->data as $index => $record) {

            DB::beginTransaction();
        
            try {
        
                $data = $this->prepareAssessmentData($record);
        
                if (!$data) {
                    DB::rollBack();
                    $this->incrementErrorCount();
                    continue;
                }
        
                $riskAssessment = $this->riskAssessmentService->store($data);
        
                if (!$riskAssessment) {
                    DB::rollBack();
                    $this->incrementErrorCount();
                    continue;
                }
        
                $riskAssessment->risk_assessment_control_id = $this->batchId;
                $riskAssessment->status = $data['status'];
                $riskAssessment->save();
        
                DB::commit();
        
                if ($riskAssessment->status === StatusAssessment::SUCESS->value) {
                    $this->incrementSuccessCount();
                } else {
                    $this->incrementErrorCount();
                }
        
            } catch (\Throwable $e) {
        
                DB::rollBack();
        
                Log::error('Erro ao processar registro', [
                    'index' => $index,
                    'error' => $e->getMessage()
                ]);
        
                $this->incrementErrorCount();
            }
        }

        Log::info('ImportDataJob finalizado', [
            'batchId' => $this->batchId
        ]);
    }

    private function prepareAssessmentData(array $record): ?array
    {

        if (empty($record['social_denomination'])) {
            return null;
        }

        $entity = $this->entityService->storeOrUpdate(
            [
                'policy_number' => $record['policy_number'] ?? null,
                'customer_number' => $record['customer_number'] ?? null,
                'nif' => $record['nif'] ?? null,
            ],
            [
                'policy_number' => $record['policy_number'] ?? null,
                'customer_number' => $record['customer_number'] ?? null,
                'nif' => $record['nif'] ?? null,
                'social_denomination' => $record['social_denomination'],
                'entity_type' => $record['entity_type'] ?? null,
                'pep' => $this->normalizeBoolean($record['pep'] ?? false),
            ]
        );

        $productRisks = $record['product_risk'] ?? [];

        if (!is_array($productRisks)) {
            $productRisks = [$productRisks];
        }

        $productRiskIds = array_filter(
            array_map(fn($r) => $this->indicatorService->getByDescription($r) ?: null, $productRisks)
        );

        $beneficialOwners = [];

        if (!empty($record['beneficial_owner'])) {
            $beneficialOwners[] = [
                'name' => $record['beneficial_owner'],
                'pep' => $this->normalizeBoolean($record['pep'] ?? false)
            ];
        }

        $data = [
            'entity_id' => $entity->id,
            'identification_capacity' => 1,
            'professionP' => $this->indicatorService->getByDescription($record['profession'] ?? ''),
            'categoryP' => $this->indicatorService->getByDescription($record['category'] ?? ''),
            'channel' => $this->indicatorService->getByDescription($record['channel'] ?? ''),
            'product_risk' => $productRiskIds,
            'country_residence' => $this->indicatorService->getByDescription($record['country_residence'] ?? ''),
            'nationality' => $this->indicatorService->getByDescription($record['nationality'] ?? ''),
            'form_establishment' => $this->normalizeBoolean($record['form_establishment'] ?? false),
            'status_residence' => $this->normalizeBoolean($record['status_residence'] ?? false),
            'pep' => $this->normalizeBoolean($record['pep'] ?? false),
            'beneficial_owner' => $beneficialOwners,
            'type_assessment' => TypeAssessment::IMPORT->value,
            'user_id' => $this->userID,
            'risk_assessment_control_id' => $this->batchId,
            'created_at' => now(),
            'updated_at' => now(),
        ];

        $requiredFields = [
            $data['professionP'],
            $data['categoryP'],
            $data['channel'],
            $data['country_residence'],
            $data['nationality'],
        ];

        $hasNullField = collect($requiredFields)->contains(fn($field) => is_null($field));

        $hasEmptyProductRisk = empty($data['product_risk']);

        $data['status'] = ($hasNullField || $hasEmptyProductRisk)
            ? StatusAssessment::ERROR->value
            : StatusAssessment::SUCESS->value;

        return $data;
    }

    private function normalizeBoolean($value): int
    {
        return ($value === true || $value === 1 || $value === '1' || $value === 'true') ? 1 : 0;
    }

    private function incrementSuccessCount()
    {
        $record = RiskAssessmentControl::find($this->batchId);

        if ($record) {
            $record->increment('total_sucess');
            $record->increment('total');
        }
    }

    private function incrementErrorCount()
    {
        $record = RiskAssessmentControl::find($this->batchId);

        if ($record) {
            $record->increment('total_error');
            $record->increment('total');
        }
    }
}