<?php

namespace App\Jobs;

use App\Enum\TypeAssessment;
use Illuminate\Bus\Queueable;
use App\Enum\StatusAssessment;
use App\External\PepExternalApi;
use App\External\SanctionExternalApi;
use Illuminate\Support\Facades\Cache;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Models\Entities\RiskAssessmentControl;
use App\Repositories\Alert\AlertRepository;
use App\Services\Entities\EntitiesService;
use App\Services\Entities\RiskAssessmentService;
use App\Services\Indicator\IndicatorTypeService;
use App\Traits\DatabaseLogger;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
class ImportDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, DatabaseLogger;

    protected $data;
    protected $userID;
    protected $batchId;
    private $riskAssessmentService;
    protected $alertRepository;
    public function __construct(array $data, $userID, $batchId)
    {
        $this->userID = $userID;
        $this->data = $data;
        $this->batchId = $batchId;
    }

    public $tries = 5;
    public $timeout = 36000; // 10 horas

    public function handle(RiskAssessmentService $riskAssessmentService,   AlertRepository $alertRepository)
    {
        $this->riskAssessmentService = $riskAssessmentService;
        $this->alertRepository = $alertRepository;
        foreach ($this->data as $record) {
            DB::beginTransaction();
            try {
                $this->processRecord($record);
                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                $this->incrementErrorCount();
                continue;
            }
        }
    }

    private function processRecord(array $record)
    {
        if (empty($record['policy_number']) || empty($record['customer_number']) || empty($record['social_denomination'])) {
            return;
        }

        Auth::loginUsingId($this->userID);

        $data = $this->prepareAssessmentData($record);
        $data['user_id'] = $this->userID;
        $data['type_assessment'] = TypeAssessment::IMPORT->value; // Import type
        if ($data["status"] == 1) {
            $this->incrementSuccessCount();
        } else {
            $this->incrementErrorCount();
        }
        $riskAssessment = $this->riskAssessmentService->store($data);
        if ($riskAssessment) {
            $riskAssessment->risk_assessment_control_id = $this->batchId;
            $this->logToDatabase(
                type: 'entity',
                level: 'info',
                customMessage: "{$riskAssessment?->entity?->social_denomination} realizou uma avaliação na entidade {$riskAssessment?->entity?->social_denomination} que resultou em uma pontuação de {$riskAssessment->score} com um nível de risco {$riskAssessment->risk_level} e o tipo de diligência {$riskAssessment->diligence}.",
                idEntity: $riskAssessment->entity_id
            );
            $userName = auth()->user()?->first_name ?? 'Usuário desconhecido';
            $this->logToDatabase(
                type: 'user',
                level: 'info',
                customMessage: "{$userName} realizou uma avaliação que resultou em uma pontuação de {$riskAssessment->score} com um nível de risco {$riskAssessment->risk_level} e o tipo de diligência {$riskAssessment->diligence}.",
                idEntity: $riskAssessment->entity_id
            );
            $riskAssessment->save();
        }
    }

    private function prepareAssessmentData(array $record): array
    {
        $indicatorService = app(IndicatorTypeService::class);
        $entityService = app(EntitiesService::class);

        // Criar/atualizar entidade
        $entity = $entityService->storeOrUpdate(
            [
                'policy_number'   => $record['policy_number'] ?? null,
                'customer_number' => $record['customer_number'] ?? null,
                'nif' => $record['nif'] ?? null,
            ],
            [
                'policy_number'        => $record['policy_number'] ?? null,
                'nif'        => $record['nif'] ?? null,
                
                'customer_number'      => $record['customer_number'] ?? null,
                'social_denomination'  => $record['social_denomination'] ?? null,
                'entity_type'          => $record['entity_type'] ?? null,
                'pep'                  => $this->normalizePepValue($record['pep'] ?? false),
            ]
        );

        // Normalizar risks: pode vir string única ou array
        $productRisks = $record['product_risk'] ?? [];
        if (!is_array($productRisks)) {
            $productRisks = [$productRisks];
        }

        // Normalizar beneficiários
        $beneficialOwners = [];
        if (!empty($record['beneficial_owner'])) {
            $beneficialOwners[] = [
                'name' => $record['beneficial_owner'],
                'pep'  => $this->normalizePepValue($record['pep'] ?? false),
            ];
        }

        $data = [
            'entity_id'              => $entity->id,
            'identification_capacity' => 1, // fixo por enquanto
            'profession'             => $indicatorService->getByDescription($record['profession'] ?? null),
            'form_establishment'     => (bool)($record['form_establishment'] ?? false),
            'status_residence'       => (bool)($record['status_residence'] ?? false),
            'category'               => $indicatorService->getByDescription($record['category'] ?? null),
            'channel'                => $indicatorService->getByDescription($record['channel'] ?? null),
            'product_risk'           => array_filter(array_map(
                fn($risk) => $indicatorService->getByDescription($risk),
                $productRisks
            )),
            'country_residence'      => $indicatorService->getByDescription($record['country_residence'] ?? null),
            'nationality'            => $indicatorService->getByDescription($record['nationality'] ?? null),
            'beneficial_owner'       => $beneficialOwners,
        ];

        // Required fields
        $requiredFields = [
            'identification_capacity',
            'profession',
            'category',
            'channel',
            'product_risk',
            'country_residence',
            'nationality',
        ];

        $data['status'] = StatusAssessment::SUCESS->value;

        // Checar externos
        $externalData = PepExternalApi::getDataPepExternal($entity->social_denomination);
        $externalDataSanction = SanctionExternalApi::getDataSanctionExternal($entity->social_denomination);

        try {
            $externalData = PepExternalApi::getDataPepExternal($entity->social_denomination);
            if (!empty($externalData)) {
                $this->createAlerts($externalData, $entity->id, "PEP");
            }
        } catch (\Exception $e) {
            Log::error("Erro PEP API: " . $e->getMessage());
        }
        
        try {
            $externalDataSanction = SanctionExternalApi::getDataSanctionExternal($entity->social_denomination);
            if (!empty($externalDataSanction)) {
                $this->createAlerts($externalDataSanction, $entity->id, "SANCTIONS");
            }
        } catch (\Exception $e) {
            Log::error("Erro SANCTION API: " . $e->getMessage());
        }
        

        return $data;
    }

    private function normalizePepValue($pep): int
    {
        if ($pep === "") return 0;
        return $pep ? 1 : 0;
    }

    private function incrementSuccessCount()
    {
        $errorRecord = RiskAssessmentControl::find($this->batchId);

        if ($errorRecord) {
            $errorRecord->update([
                'total_sucess' => $errorRecord->total_sucess + 1,
                'total' => $errorRecord->total + 1,
            ]);
        }
    }

    private function incrementErrorCount()
    {
        $errorRecord = RiskAssessmentControl::find($this->batchId);

        if ($errorRecord) {
            $errorRecord->update([
                'total_error' => $errorRecord->total_error + 1,
                'total' => $errorRecord->total + 1,
            ]);
        }
    }


    private function createAlerts(array $data, int $entityId, string $type): void
    {
        foreach ($data as $item) {
            $alert=    $this->alertRepository->storeOrUpdate(
                ['origin_id' => $item['id']],
                [
                    'name' => $item['name'],
                    'level' => 'Alto',
                    'from_id' => $entityId,
                    'origin_id' => $item['id'],
                    'entity_id' => $entityId,
                    'score' => $item['score'] ?? 0,
                    'type' => match ($type) {
                    'PEP' => 'PEP List world',
                    'SANCTIONS' => 'Sanctions List',
                    'KYC' => 'KYC List',
                    default => 'KYC List',
                },
                 'category' => "KYC",
                    'list' => $item['datasets'],
                    'is_active' => true,
                ]
            );
            SendGrupoAlertEmailJob::dispatch( $alert->id);
        }
    }
}
