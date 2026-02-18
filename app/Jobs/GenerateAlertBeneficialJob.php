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

    public int $tries = 3;
    public int $timeout = 120;

    protected int $beneficialId;
    protected int $riskAssessmentId;

    public function __construct(int $beneficialId, int $riskAssessmentId)
    {
        $this->beneficialId = $beneficialId;
        $this->riskAssessmentId = $riskAssessmentId;
    }

 public function handle(
    AlertRepository $alertRepository,
    RiskAssessmentService $riskAssessmentService
): void {

    $lockKey = "beneficial-alert-lock-{$this->beneficialId}";
    $lock = Cache::lock($lockKey, 120);

    if (!$lock->get()) {
        Log::warning('Job já em execução para beneficiário', [
            'beneficial_id' => $this->beneficialId
        ]);
        return;
    }

    try {
        // Buscar RiskAssessment com product_risk e relacionamento product
        $riskAssessment = $riskAssessmentService->show($this->riskAssessmentId);

        $beneficial = Beneficial::findOrFail($this->beneficialId);

        Log::info('Geração de alertas iniciada', [
            'beneficial_id' => $beneficial->id,
            'risk_assessment_id' => $riskAssessment->id
        ]);

        // Processar PEP
        $this->processAlerts(
            PepExternalApi::getDataPepExternal($beneficial->name)['data'] ?? [],
            'PEP',
            'PEP List world',
            $beneficial,
            $riskAssessment,
            $alertRepository
        );

        // Processar Sanctions
        $this->processAlerts(
            SanctionExternalApi::getDataSanctionExternal($beneficial->name)['data'] ?? [],
            'SANCTIONS',
            'Sanctions List',
            $beneficial,
            $riskAssessment,
            $alertRepository
        );

    } catch (Throwable $e) {
        Log::error('Erro ao gerar alertas do beneficiário', [
            'beneficial_id' => $this->beneficialId,
            'error' => $e->getMessage()
        ]);

        $this->fail($e);

    } finally {
        optional($lock)->release();
    }
}

private function processAlerts(
    array $items,
    string $type,
    string $defaultList,
    Beneficial $beneficial,
    $riskAssessment,
    AlertRepository $repo
): void {
    if (empty($items)) {
        return;
    }

    foreach ($items as $item) {
        if (empty($item['id'])) {
            continue;
        }

        $score = (int) ($item['score'] ?? 0);

        $level = match (true) {
            $score >= 70 => 'Alto',
            $score >= 50 => 'Médio',
            default      => 'Baixo',
        };

        // Extrair produtos corretamente
        $productDescriptions = [];
        foreach ($riskAssessment->product_risk as $productRisk) {
            if (!empty($productRisk->product?->description)) {
                $productDescriptions[] = $productRisk->product->description;
            }
        }

        $productList = !empty($productDescriptions)
            ? implode('; ', $productDescriptions)
            : 'Produto(s) não definido(s)';

        $description = sprintf(
            'No âmbito da avaliação de risco AML/KYC, foi identificada correspondência do beneficiário %s (%s) '
            . 'em listas externas de monitorização (%s), para os seguintes produtos: %s.',
            $beneficial->name,
            $beneficial->nationality ?? 'Desconhecida',
            $defaultList,
            $productList
        );

        $repo->store([
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
    }
}

}
