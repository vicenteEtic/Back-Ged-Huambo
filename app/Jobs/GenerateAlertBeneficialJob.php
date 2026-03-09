<?php

namespace App\Jobs;

use App\External\PepExternalApi;
use App\External\SanctionExternalApi;
use App\Models\Entities\Beneficial;
use App\Repositories\Alert\AlertRepository;
use App\Services\Entities\RiskAssessmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateAlertBeneficialJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 120;

    protected $beneficialId;
    protected $riskAssessmentId;

    public function __construct(int $beneficialId, int $riskAssessmentId)
    {
        $this->beneficialId = $beneficialId;
        $this->riskAssessmentId = $riskAssessmentId;
    }

    public function handle(AlertRepository $alertRepository, RiskAssessmentService $riskAssessmentService): void
    {
        if (!$this->beneficialId || !$this->riskAssessmentId) {
            Log::error('GenerateAlertBeneficialJob: IDs ausentes', [
                'beneficialId' => $this->beneficialId,
                'riskAssessmentId' => $this->riskAssessmentId,
            ]);
            return;
        }

        $lock = Cache::lock("beneficial-alert-lock-{$this->beneficialId}", 120);

        if (! $lock->get()) {
            Log::warning('Job já em execução para beneficiário', [
                'beneficial_id' => $this->beneficialId,
            ]);
            return;
        }

        try {
            $riskAssessment = $riskAssessmentService->findModelWithProducts($this->riskAssessmentId);
            if (!$riskAssessment) {
                Log::warning("RiskAssessment não encontrado: {$this->riskAssessmentId}");
                return;
            }

            $beneficial = Beneficial::findOrFail($this->beneficialId);

            Log::info('Disparando geração de alertas', [
                'beneficial_id' => $beneficial->id,
                'name' => $beneficial->name,
                'risk_assessment_id' => $riskAssessment->id,
            ]);

            $pepResponse = PepExternalApi::getDataPepExternal($beneficial->name) ?? ['data' => []];
            $sanctionResponse = SanctionExternalApi::getDataSanctionExternal($beneficial->name) ?? ['data' => []];

            $this->generateAlerts($pepResponse, 'PEP', 'PEP List World', $beneficial, $riskAssessment, $alertRepository);
            $this->generateAlerts($sanctionResponse, 'SANCTIONS', 'Sanctions List', $beneficial, $riskAssessment, $alertRepository);
        } catch (Throwable $e) {
            Log::error('Erro ao gerar alertas do beneficiário', [
                'beneficial_id' => $this->beneficialId,
                'message' => $e->getMessage(),
            ]);
            $this->fail($e);
        } finally {
            optional($lock)->release();
        }
    }
   private function generateAlerts(
    $response,
    string $type,
    string $defaultList,
    Beneficial $beneficial,
    $riskAssessment,
    AlertRepository $repo
): void {

    // Garante que $response seja array
    if (!is_array($response)) {
        Log::warning("Resposta inválida da API para {$beneficial->name}", [
            'type' => $type,
            'response' => $response
        ]);
        $response = ['data' => []];
    }

    // Garante que $items seja sempre um array
    $items = $response['data'] ?? [];
    if (!is_array($items)) {
        Log::warning("Campo 'data' inválido na resposta da API para {$beneficial->name}", [
            'type' => $type,
            'data_field' => $response['data'] ?? null
        ]);
        $items = [];
    }

    if (empty($items)) {
        Log::info("Nenhum alerta encontrado para {$beneficial->name} na lista {$defaultList}");
        return;
    }

    foreach ($items as $item) {
        // Ignora itens sem ID
        if (empty($item['id'])) {
            Log::warning("Item ignorado sem 'id' em {$defaultList}", ['item' => $item]);
            continue;
        }

        $score = isset($item['score']) ? (int)$item['score'] : 0;
        $level = match (true) {
            $score >= 70 => 'Alto',
            $score >= 50 => 'Médio',
            default => 'Baixo',
        };

        $description = sprintf(
            'No âmbito da avaliação de risco AML/KYC, foi identificada correspondência do beneficiário %s (%s) '
            . 'em listas externas de monitorização (%s).',
            $beneficial->name,
            $beneficial->nationality ?? 'Desconhecida',
            $defaultList
        );

        $dateValidate = [
            'origin_id' => $item['id'],
            'entity_id' => $riskAssessment->entity_id,
            'type'      => $type,
        ];

        $repo->firstOrCreate($dateValidate, [
            'origin_id'   => $item['id'],
            'entity_id'   => $riskAssessment->entity_id,
            'from_id'     => $beneficial->id,
            'name'        => $item['name'] ?? 'Desconhecido',
            'country'     => $item['country'] ?? null,
            'birth_date'  => $item['birth_date'] ?? null,
            'score'       => $score,
            'level'       => $level,
            'type'        => $type,
            'category'    => 'KYC',
            'list'        => $item['datasets'] ?? $defaultList,
            'is_active'   => true,
            'description' => $description,
        ]);

        Log::info("Alerta gerado para {$beneficial->name}", [
            'type'      => $type,
            'origin_id' => $item['id'],
            'score'     => $score,
            'level'     => $level,
        ]);
    }
}
}
