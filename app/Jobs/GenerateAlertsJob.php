<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use App\External\PepExternalApi;
use Illuminate\Support\Facades\Log;
use App\External\SanctionExternalApi;
use App\Models\Entities\Entities;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use App\Repositories\Alert\AlertRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use App\Repositories\Entities\EntitiesRepository;

class GenerateAlertsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $entityId;
    protected string $riskAssessment;
    protected $alertRepository;
    protected $entitiesRepository;

    /**
     * @param int    $entityId
     * @param string $riskAssessment
     */
    public function __construct(int $entityId,  $riskAssessment)
    {
        $this->entityId = $entityId;
        $this->riskAssessment = $riskAssessment;
    }

    /**
     * Execute the job.
     */
    public function handle(
        AlertRepository $alertRepository,
        EntitiesRepository $entitiesRepository
    ): void {
        $this->alertRepository = $alertRepository;
        $this->entitiesRepository = $entitiesRepository;

        $this->generateAlertGeneral(
            $this->entityId,
            $this->riskAssessment
        );
        Log::info('GenerateAlertsJob iniciado', ['entityId' => $this->entityId, 'riskAssessment' => $this->riskAssessment]);
    }

    /**
     * Generate alerts for the given entity.
     */
    public function generateAlertGeneral(int $entityId,  $riskAssessment): void
    {
        $entity = Entities::find($entityId);
        if (!$entity) {
            Log::warning("Entity with ID {$entityId} não encontrada");
            return;
        }
        $entityName = $entity->social_denomination ?? null;
        // Consultar APIs externas
        $externalData = PepExternalApi::getDataPepExternal($entityName);
        $externalDataSanction = SanctionExternalApi::getDataSanctionExternal($entityName);

        // Registrar nos logs
        Log::info('externalData:', $externalData);

        Log::info('externalDataSanction', $externalData);


        // Criar alertas se houver retorno
        if (!empty($externalData['data'])) {
            $this->createAlerts($externalData['data'], $entityId, 'PEP');
        }

        if (!empty($externalDataSanction['data'])) {
            $this->createAlerts($externalDataSanction['data'], $entityId, 'SANCTIONS');
        }
    }


    /**
     * Process entities for PEP checks.
     */
    public function processEntities($entities, string $nameField): void
    {
        foreach ($entities as $entity) {
            $entityName = $entity->$nameField;
            try {
                $externalData = PepExternalApi::getDataPepExternal($entityName);

                if (empty($externalData)) {
                    continue;
                }

                $this->createAlerts($externalData, $entity->id, "PEP");
            } catch (\Exception $e) {
                Log::error("Error processing entity {$entityName}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Process entities for sanctions checks.
     */
    public function processEntitiesSanctions($entities, string $nameField): void
    {
        foreach ($entities as $entity) {
            $entityName = $entity->$nameField;
            try {
                $externalData = SanctionExternalApi::getDataSanctionExternal($entityName);
                if (empty($externalData)) {
                    continue;
                }

                $this->createAlerts($externalData, $entity->id, "SANCTIONS");
            } catch (\Exception $e) {
                Log::error("Error processing entity {$entityName}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Create or update alerts based on external data.
     */

   private function resolveAlertType(string $source): array
{
    return match ($source) {
        'PEP' => [
            'type' => 'PEP',
            'category' => 'PEP',
            'list' => 'PEP List world',
            'is_pep' => 1,
            'is_sanctioned' => 0,
        ],
        'SANCTIONS' => [
            'type' => 'SANCTIONS',
            'category' => 'SANCTIONS',
            'list' => 'Sanctions List',
            'is_pep' => 0,
            'is_sanctioned' => 1,
        ],
        'KYC' => [
            'type' => 'KYC',
            'category' => 'KYC',
            'list' => 'KYC List',
            'is_pep' => 0,
            'is_sanctioned' => 0,
        ],
        default => throw new \InvalidArgumentException("Tipo de alerta inválido: {$source}"),
    };
}


 private function createAlerts(array $data, int $entityId, string $source): void
{
    $typeData = $this->resolveAlertType($source); // resolver tipo/lista

    foreach ($data as $item) {
        $level = match (true) {
            ($item['score'] ?? 0) >= 70 => 'Alto',
            ($item['score'] ?? 0) >= 50 => 'Médio',
            default => 'Baixo',
        };

        $alert = $this->alertRepository->storeOrUpdate(
            [
                'origin_id' => $item['id'],
                'entity_id' => $entityId,
                'type'      => $typeData['type'],
            ],
            [
                'name'         => $item['name'],
                'country'      => $item['country'] ?? null,
                'birth_date'   => $item['birth_date'] ?? null,
                'level'        => $level,
                'from_id'      => $entityId,
                'origin_id'    => $item['id'],
                'entity_id'    => $entityId,
                'score'        => $item['score'] ?? 0,
                'type'         => "PEP List world",       
                'category'     => $typeData['category'],   // mesma classificação
                'list'         =>  $item['datasets'] ?? "PEP List world",       // nome da lista
                'is_active'    => true,
            ]
        );

        SendGrupoAlertEmailJob::dispatch($alert->id)->onQueue('high');
    }
}


}
