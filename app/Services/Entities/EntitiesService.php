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
        $timeLimit = Carbon::now()->subSeconds(self::TIME_LIMIT_SECONDS);
        $existingRecord = $this->riskAssessmentControlService->findOneBy(
            [
                [
                    'updated_at',
                    '>=',
                    $timeLimit
                ]
            ]
        );

        if (!$existingRecord) {
            $dataArray = $this->riskAssessmentControlService->store([
                'total_sucess' => 0,
                'error' => 0,
                'user_id' => $userId,
                'total_error' => 0,
                'total' => 0
            ]);
            $recordId = $dataArray->id;
        } else {
            $recordId = $existingRecord->id;
        }
        return $recordId;
    }

    public function dispatchImportJobs(array $data, int $userId, int $batchId): void
    {
        $chunks = array_chunk($data, self::BATCH_SIZE);

        foreach ($chunks as $index => $chunk) {
            ImportDataJob::dispatch($chunk, $userId, $batchId)
                ->onQueue('default')
                ->delay(Carbon::now()->addSeconds($index * 10)); // garante Carbon

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
