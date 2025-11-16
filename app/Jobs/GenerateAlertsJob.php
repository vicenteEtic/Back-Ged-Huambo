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
    public function __construct(int $entityId, string $riskAssessment)
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
    }

    /**
     * Generate alerts for the given entity.
     */
    public function generateAlertGeneral(int $entityId, string $riskAssessment): void
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
   
        // Criar alertas se houver retorno
        if (!empty($externalData)) {
            $this->createAlerts($externalData, $entity->id, "PEP");
        }
        if (!empty($externalDataSanction)) {
            $this->createAlerts($externalDataSanction, $entity->id, "SANCTION");
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

                $this->createAlerts($externalData, $entity->id, "SANCTION");
            } catch (\Exception $e) {
                Log::error("Error processing entity {$entityName}: {$e->getMessage()}");
            }
        }
    }

    /**
     * Create or update alerts based on external data.
     */
    private function createAlerts(array $data, int $entityId, string $type = "PEP"): void
    {
        foreach ($data as $item) {
            $alert=    $this->alertRepository->storeOrUpdate(
                ['origin_id' => $item['id'],
               'name' => $item['name'],],
                [
                    'name' => $item['name'],
                    'level' => 'Alto',
                    'from_id' => $entityId,
                    'origin_id' => $item['id'],
                    'entity_id' => $entityId,
                    'score' => $item['score'] ?? 0,
                    'type' => $type,
                    'list' => $item['type'] ?? ($type === "PEP" ? "PEP List world" : "Sanctions List"),
                    'is_active' => true,
                ]
            );
            SendGrupoAlertEmailJob::dispatch( $alert->id);
        }
    }
}
