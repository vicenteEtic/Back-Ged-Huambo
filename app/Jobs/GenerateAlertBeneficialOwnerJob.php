<?php

namespace App\Jobs;

use App\External\PepExternalApi;
use App\External\SanctionExternalApi;
use App\Models\Entities\BeneficialOwner;
use App\Repositories\Alert\AlertRepository;
use App\Repositories\Entities\BeneficialOwnerRepository;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Jobs\SendGrupoAlertEmailJob;

class GenerateAlertBeneficialOwnerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected int $beneficialOwnerID;
    protected int $entity_id;

    protected AlertRepository $alertRepository;
    protected BeneficialOwnerRepository $beneficialOwnerRepository;

    public $tries = 10;
    public $timeout = 120;

    public function __construct(int $beneficialOwnerID, int $entity_id)
    {
        $this->beneficialOwnerID = $beneficialOwnerID;
        $this->entity_id = $entity_id;
    }

    public function handle(AlertRepository $alertRepository, BeneficialOwnerRepository $beneficialOwnerRepository): void
    {
        $this->alertRepository = $alertRepository;
        $this->beneficialOwnerRepository = $beneficialOwnerRepository;

        Log::info('GenerateAlertBeneficialOwnerJob iniciado', [
            'beneficialOwnerID' => $this->beneficialOwnerID,
            'entity_id' => $this->entity_id
        ]);

        $this->generateAlertGeneral($this->beneficialOwnerID, $this->entity_id);
    }

    private function generateAlertGeneral(int $beneficialOwnerID, int $entity_id): void
    {
        $beneficialOwner = BeneficialOwner::find($beneficialOwnerID);

        if (!$beneficialOwner) {
            Log::warning("BeneficialOwner com ID {$beneficialOwnerID} não encontrado");
            return;
        }

        $name = $beneficialOwner->name;
        if (empty($name)) {
            Log::warning("BeneficialOwner sem nome (ID: {$beneficialOwnerID})");
            return;
        }

        try {
            $pepData = PepExternalApi::getDataPepExternal($name);
            $sanctionData = SanctionExternalApi::getDataSanctionExternal($name);

            Log::info('PEP Data', $pepData ?? []);
            Log::info('Sanctions Data', $sanctionData ?? []);

            if (!empty($pepData['data'])) {
                $this->createAlerts($pepData['data'], $entity_id, $name, 'PEP');
            }

            if (!empty($sanctionData['data'])) {
                $this->createAlerts($sanctionData['data'], $entity_id, $name, 'SANCTIONS');
            }
        } catch (\Throwable $e) {
            Log::error("Erro nas APIs externas: " . $e->getMessage());
        }
    }

    private function createAlerts(array $data, int $entity_id, string $name, string $type): void
    {
        $typeData = $this->resolveType($type);

        foreach ($data as $item) {
            $score = $item['score'] ?? 0;

            $level = match (true) {
                $score >= 70 => 'Alto',
                $score >= 50 => 'Médio',
                default      => 'Baixo',
            };
            $description = sprintf(
                'No âmbito da avaliação de risco AML/KYC, foi identificada correspondência do beneficiário efetivo %s em listas externas (%s).',
                $name,
                $item['datasets'] ?? $typeData['list']
            );
            $criteria = [
                'entity_id' => $entity_id,
                'origin_id' => '#'.$entity_id ?? null,
             
            ];

            $alert = $this->alertRepository->findByValidate($criteria);

            if (!$alert) {


                $alert = $this->alertRepository->store([
                    'name'        => $item['name'] ?? 'UNKNOWN',
                    'country'     => $item['country'] ?? null,
                    'birth_date'  => $item['birth_date'] ?? null,
                    'level'       => $level,
                    'from_id'     => $entity_id,
                    'origin_id'   => $item['id'] ?? null,
                    'entity_id'   => $entity_id,
                    'score'       => $score,
                    'type'        => $typeData['type'],
                    'category'    => 'KYC',
                    'list'        => $item['datasets'] ?? $typeData['list'],
                    'description' => $description,
                    'is_active'   => true,
                ]);

                Log::info('Alerta criado', ['alert_id' => $alert->id]);
            }

            try {
                $host = config('app.url');
                SendGrupoAlertEmailJob::dispatch($alert->id, $host)->onQueue('high');
            } catch (\Throwable $th) {
                Log::error("Erro ao enviar email: " . $th->getMessage());
            }
        }
    }

    private function resolveType(string $type): array
    {
        return match ($type) {
            'PEP' => ['type' => 'PEP', 'list' => 'PEP List world', 'is_pep' => 1, 'is_sanctioned' => 0],
            'SANCTIONS' => ['type' => 'SANCTIONS', 'list' => 'Sanctions List', 'is_pep' => 0, 'is_sanctioned' => 1],
            default => ['type' => 'KYC', 'list' => 'KYC List', 'is_pep' => 0, 'is_sanctioned' => 0],
        };
    }
}
