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
    // Procura batch ativo (não processando)
    $existingRecord = $this->riskAssessmentControlService->findOneBy([
        ['user_id', '=', $userId],
        ['is_processing', '=', 1] // ou 0 se você quiser pegar batch livre
    ]);

    if ($existingRecord) {
        // Usa o batch existente
        return $existingRecord->id;
    }

    // Se não existe, cria um novo
    $record = $this->riskAssessmentControlService->store([
        'total_sucess' => 0,
        'total_error' => 0,
        'total' => 0,
        'user_id' => $userId,
        'is_processing' => 0
    ]);

    return $record->id;
}

    public function dispatchImportJobs(array $data, int $userId, int $batchId): void
    {
     // 1️⃣ Inicializa batch uma única vez
    $batchId = $this->initializeImportBatch($userId);

    // 2️⃣ Divide dados em chunks
    $chunks = array_chunk($data, self::BATCH_SIZE);

    // 3️⃣ Dispara jobs para a fila 'high'
    foreach ($chunks as $index => $chunk) {
        ImportDataJob::dispatch($chunk, $userId, $batchId)
            ->onQueue('high')
            ->delay(Carbon::now()->addSeconds($index * 10));
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
