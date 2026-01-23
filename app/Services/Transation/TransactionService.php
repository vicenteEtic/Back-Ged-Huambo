<?php

namespace App\Services\Transation;

use App\Jobs\MonitorCustomerActivity;
use App\Repositories\Transation\TransactionRepository;
use App\Services\AbstractService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Bus\Batch;
use Illuminate\Support\Facades\Bus;
use App\Jobs\TransationJob;
use App\Models\Entities\Entities;

class TransactionService extends AbstractService
{
    private const BATCH_SIZE = 2000;

    public function __construct(
        TransactionRepository $repository,
        private readonly transaionControlService $transaionControlService
    ) {
        parent::__construct($repository);
    }

    public function initializeImportBatch(): int
    {
        $userID = Auth::id();

        $dataArray = $this->transaionControlService->store([
            'user_id' => $userID,
            'total'   => 0
        ]);

        return $dataArray->id;
    }

  public function dispatchImportJobs(array $data, $userId): string
{
    $batchId = $this->initializeImportBatch();

    $chunks = array_chunk($data, self::BATCH_SIZE);

    $jobs = [];
    foreach ($chunks as $chunk) {
        $jobs[] = new TransationJob($chunk, $userId, $batchId);
    }

    $batch = Bus::batch($jobs)
        ->name("Importação - {$userId}")
        ->then(function () {

            // Só depois que a importação terminar
            Entities::chunk(200, function ($customers) {
                $ids = $customers->pluck('id')->toArray();
                MonitorCustomerActivity::dispatch($ids);
            });

        })
        ->dispatch();

    return $batch->id;
}


}
