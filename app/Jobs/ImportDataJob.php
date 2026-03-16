<?php

namespace App\Jobs;

use App\Enum\TypeAssessment;
use App\Enum\StatusAssessment;
use App\Models\Entities\Entities;
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
use Dom\Entity;

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

        // Recupera controle do batch
        $control = RiskAssessmentControl::find($this->batchId);

        if (!$control) {
            Log::error("Batch {$this->batchId} não encontrado.");
            return;
        }

        // Bloqueia reprocessamento
        if ($control->is_processing) {
            Log::warning("Batch {$this->batchId} já está em processamento.");
            return;
        }

        $control->is_processing = true;
        $control->save();

        Log::info("ImportDataJob iniciado", [
            'batchId' => $this->batchId,
            'total_records' => count($this->data)
        ]);

        Auth::loginUsingId($this->userID);

        // Processa dados em blocos de 1000 registros para não sobrecarregar memória
        $chunks = array_chunk($this->data, 1000);

        foreach ($chunks as $chunkIndex => $chunk) {
            DB::transaction(function () use ($chunk, $control, $chunkIndex) {
                foreach ($chunk as $index => $record) {
                    try {
                        $data = $this->prepareAssessmentData($record);

                        $riskAssessment = $this->riskAssessmentService->store($data);
                        $riskAssessment->risk_assessment_control_id = $this->batchId;
                        $riskAssessment->status = $data['status'];
                        $riskAssessment->save();

                        if ($riskAssessment->status === StatusAssessment::SUCESS->value) {
                            $this->incrementSuccessCount($control);
                        } else {
                            $this->incrementErrorCount($control);
                        }
                    } catch (\Throwable $e) {
                        Log::error("Erro no registro do chunk {$chunkIndex}, index {$index}", [
                            'error' => $e->getMessage()
                        ]);
                        $this->incrementErrorCount($control);
                    }
                }
            });
        }

        // Libera batch
        $control->is_processing = false;
        $control->save();

        Log::info('ImportDataJob finalizado', [
            'batchId' => $this->batchId
        ]);
    }

private function prepareAssessmentData(array $record): array
{
    // Cria ou atualiza entidade (mesmo com campos nulos)
    $entity = Entities::updateOrCreate(
        ['nif' => $record['nif'] ?? null],
        [
            'policy_number' => $record['policy_number'] ?? null,
            'customer_number' => $record['customer_number'] ?? null,
            'social_denomination' => $record['social_denomination'] ?? null,
            'entity_type' => $record['entity_type'] ?? null,
        ]
    );

    $data = [
        'entity_id' => $entity->id,
        'identification_capacity' => $this->indicatorService->getByDescription($record['identification_capacity'] ?? null),
        'professionP' => $this->indicatorService->getByDescription($record['profession'] ?? null),
        'categoryP' => $this->indicatorService->getByDescription($record['category'] ?? null),
        'channel' => $this->indicatorService->getByDescription($record['channel'] ?? null),
        'product_risk' => $record['product_risk'] ?? [],
        'country_residence' => $this->indicatorService->getByDescription($record['country_residence'] ?? null),
        'nationality' => $this->indicatorService->getByDescription($record['nationality'] ?? null),
        'form_establishment' => $this->normalizeBoolean($record['form_establishment'] ?? false),
        'status_residence' => $this->normalizeBoolean($record['status_residence'] ?? false),
        'pep' => $this->normalizeBoolean($record['pep'] ?? false),
        'santion' => $this->normalizeBoolean($record['sanction'] ?? false),
        'beneficial_owners' => $record['beneficial_owners'] ?? [],
        'beneficiaries' => $record['beneficiaries'] ?? [],
        'type_assessment' => 2,
        'user_id' => $this->userID,
        'risk_assessment_control_id' => $this->batchId,
    ];

    // Verifica campos obrigatórios
    $requiredFields = [
        $data['professionP'],
        $data['categoryP'],
        $data['channel'],
        $data['country_residence'],
        $data['nationality'],
        $data['identification_capacity'],
    ];

    // Se algum campo obrigatório estiver vazio ou sem product_risk, marca erro
    $data['status'] = (collect($requiredFields)->contains(fn($f) => is_null($f)) || empty($data['product_risk']))
        ? StatusAssessment::ERROR->value
        : StatusAssessment::SUCESS->value;

    return $data;
}



    private function normalizeBoolean($value): int
    {
        return ($value === true || $value === 1 || $value === '1' || $value === 'true') ? 1 : 0;
    }

    private function incrementSuccessCount(RiskAssessmentControl $control)
    {
        $control->increment('total_sucess');
        $control->increment('total');
    }

    private function incrementErrorCount(RiskAssessmentControl $control)
    {
        $control->increment('total_error');
        $control->increment('total');
    }
}
