<?php

namespace App\Services\Transation;

use App\Jobs\DispatchCustomerMonitoringJobs;
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
    private const TIME_LIMIT_SECONDS = 10;

    public function __construct(
        TransactionRepository $repository,
        private readonly transaionControlService $transaionControlService
    ) {
        parent::__construct($repository);
    }



   


    public function initializeImportBatch(): int
    {
        $userID = Auth::id();
        $timeLimit = Carbon::now()->subSeconds(self::TIME_LIMIT_SECONDS);
    
        $existingRecord = $this->transaionControlService->findOneBy([
            ['updated_at', '>=', $timeLimit]
        ]);
    
        if (!$existingRecord) {
            $dataArray = $this->transaionControlService->store([
                'user_id' => $userID,
                'total'   => 0
            ]);
            $recordId = $dataArray->id;
        } else {
            $recordId = $existingRecord->id;
        }
    
        return $recordId;
    }
    

public function dispatchImportJobs(array $data, $userId): string
{
    $controlId = $this->initializeImportBatch(); // id do controle

    $chunks = array_chunk($data, self::BATCH_SIZE);

    $jobs = [];
    foreach ($chunks as $chunk) {
        $jobs[] = new TransationJob($chunk, $userId, $controlId);
    }

    $batch = Bus::batch($jobs)
        ->name("Importação - {$userId}")
        ->then(function () {
            Entities::chunk(200, function ($customers) {
                $ids = $customers->pluck('id')->toArray();
                DispatchCustomerMonitoringJobs::dispatch($ids);
            });
        })
        ->dispatch();

    return $batch->id;
}


}
