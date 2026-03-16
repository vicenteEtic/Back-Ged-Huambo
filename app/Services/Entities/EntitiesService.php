<?php

namespace App\Services\Entities;

use App\Enum\TypeEntity;
use App\Jobs\ImportDataJob;
use App\Repositories\Entities\EntitiesRepository;
use App\Services\AbstractService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class EntitiesService extends AbstractService
{

    private const BATCH_SIZE = 8000;
    private const TIME_LIMIT_SECONDS = 10;


    public function __construct(
        EntitiesRepository $repository,
        private RiskAssessmentControlService $riskAssessmentControlService
    ) {
        parent::__construct($repository);
    }

    public function getTotalEntites()
    {
        return $this->repository->getTotalEntities();
    }

    public function getEntitiesByType(TypeEntity $type)
    {
        return $this->repository->getEntitiesByType($type);
    }


    public function collectiveEntities_evaluation(): int
    {
        return $this->repository->collectiveEntities_evaluation();
    }

    public function privateEntities_evaluation()
    {
        return $this->repository->privateEntities_evaluation();
    }

   public function initializeImportBatch(int $userId): int
{
    // Procura por um batch ativo que ainda não está processando
    $existingRecord = $this->riskAssessmentControlService->findOneBy([
        ['user_id', '=', $userId],
        ['is_processing', '=', 0]
    ]);

    if ($existingRecord) {
        return $existingRecord->id;
    }

    // Cria um novo batch
    $record = $this->riskAssessmentControlService->store([
        'total_sucess' => 0,
        'total_error' => 0,
        'total' => 0,
        'user_id' => $userId,
        'is_processing' => 0
    ]);

    return $record->id;
}
public function dispatchImportJobs(array $data, int $userId): void
{
    // Inicializa o batch apenas uma vez
    $batchId = $this->initializeImportBatch($userId);

    // Divide os dados em chunks
    $chunks = array_chunk($data, self::BATCH_SIZE);

    foreach ($chunks as $index => $chunk) {
        ImportDataJob::dispatch($chunk, $userId, $batchId)
            ->onQueue('high')
            ->delay(now()->addSeconds($index * 5)); // pequenos intervalos para evitar sobreposição
    }
}
    public function getLastEntities(int $limit = 3)
    {
        return $this->repository->getLastEntities($limit);
    }
    public function findOrFail($id)
    {
        return $this->model->findOrFail($id);
    }
}
