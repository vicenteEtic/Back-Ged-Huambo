<?php

namespace App\Jobs;

use App\Models\Alert\Alert;
use App\External\PepExternalApi;
use App\External\SanctionExternalApi;
use App\Models\Entities\Entities;
use Illuminate\Support\Facades\Log;
use Illuminate\Queue\SerializesModels;
use App\Models\Entities\BeneficialOwner;
use App\Services\Alert\AlertService;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

class AlertJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, SerializesModels;

    public function handle(): void
    {
        $this->processEntities(Entities::all(), 'social_denomination');
        $this->processEntities(BeneficialOwner::all(), 'name');
        $this->processEntitiesSanctions(Entities::all(), 'social_denomination');
        $this->processEntitiesSanctions(BeneficialOwner::all(), 'name');
        Log::info("AlertJob completed successfully.");
    }

    /**
     * Process entities and create alerts based on external API data.
     *
     * @param \Illuminate\Database\Eloquent\Collection $entities
     * @param string $nameField
     * @return void
     */
    private function processEntities($entities, string $nameField): void
    {
        foreach ($entities as $entity) {
            $entityName = $entity->$nameField;
            Log::info("Processing entity: {$entityName}");

            try {
                $externalData = PepExternalApi::getDataPepExternal($entityName, "PEP");

                if (empty($externalData)) {
                    Log::info("No data found for entity: {$entityName}");
                    continue;
                }

                $this->createAlerts($externalData, $entity->id, "PEP");
            } catch (\Exception $e) {
                Log::error("Error processing entity {$entityName}: {$e->getMessage()}");
            }
        }
    }

    private function processEntitiesSanctions($entities, string $nameField): void
    {
        foreach ($entities as $entity) {
            $entityName = $entity->$nameField;
            Log::info("Processing entity: {$entityName}");

            try {
                $externalData = SanctionExternalApi::getDataSanctionExternal($entityName, "SANCTION");

                if (empty($externalData)) {
                    Log::info("No data found for entity: {$entityName}");
                    continue;
                }

                $this->createAlerts($externalData, $entity->id, "SANCTIONS");
            } catch (\Exception $e) {
                Log::error("Error processing entity {$entityName}: {$e->getMessage()}");
            }
        }
    }


    /**
     * Create alerts from external API data.
     *
     * @param array $data
     * @param int $entityId
     * @return void
     */
    private function createAlerts(array $data, int $entityId, $type): void
    {
        foreach ($data as $item) {
    
            $typeData = match ($type) {
                'PEP' => [
                    'type' => 'PEP',
                    'list' => 'PEP List world',
                    'is_pep' => 1,
                    'is_sanctioned' => 0,
                ],
                'SANCTIONS' => [
                    'type' => 'SANCTIONS',
                    'list' => 'Sanctions List',
                    'is_pep' => 0,
                    'is_sanctioned' => 1,
                ],
                default => [
                    'type' => 'KYC',
                    'list' => 'KYC List',
                    'is_pep' => 0,
                    'is_sanctioned' => 0,
                ],
            };
    
            $name  = $item['name'] ?? 'UNKNOWN';
            $score = $item['score'] ?? 0;
    
            Log::info("Creating alert for: {$name}");
    
            if ($score >= 70) {
                $level = 'Alto';
            } elseif ($score >= 50) {
                $level = 'Médio';
            } else {
                $level = 'Baixo';
            }
    
            $alert = Alert::updateOrCreate(
                [
                    'name'      => $name,
                    'entity_id'=> $entityId,
                ],
                [
                    'name'       => $name,
                    'level'      => $level,
                    'from_id'    => $entityId,
                    'origin_id'  => $item['id'] ?? null,
                    'entity_id'  => $entityId,
                    'score'      => $score,
                    'country'    => $item['country'] ?? null,
                    'birth_date' => $item['birth_date'] ?? null,
                    'type'       => $typeData['type'],
                    'list'       => $item['datasets'] ?? $typeData['list'],
                    'is_active'  => true,
                ]
            );
    
            $host = config('app.url');
            SendGrupoAlertEmailJob::dispatch($alert->id, $host)->onQueue('high');
        }
    }
    
}
