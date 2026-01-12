<?php

namespace App\Services\Transation;

use App\Jobs\MonitorCustomerActivity;
use App\Repositories\Transation\TransactionRepository;
use App\Services\AbstractService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Jobs\TransationJob;
use Illuminate\Support\Facades\Auth;

class TransactionService extends AbstractService
{


    private const BATCH_SIZE = 500;
    private const TIME_LIMIT_SECONDS = 40;
    public function __construct(TransactionRepository $repository,  private readonly transaionControlService $transaionControlService,)
    {
        parent::__construct($repository);
    }


 public function initializeImportBatch(): int
    {
        $userID=Auth::user()->id;
        $timeLimit = Carbon::now()->subSeconds(self::TIME_LIMIT_SECONDS);
        $existingRecord = $this->transaionControlService->findOneBy(
            [
                [
                    'updated_at',
                    '>=',
                    $timeLimit
                ]
            ]
        );

        if (!$existingRecord) {
            $dataArray = $this->transaionControlService->store([
                'user_id' =>   $userID ,
                'total' => 0
            ]);
            $recordId = $dataArray->id;
        } else {
            $recordId = $existingRecord->id;
        }
        return $recordId;
    }


public function dispatchImportJobs(array $data, $userId): string
{
    $batchId =$this->initializeImportBatch();

    $chunks = array_chunk($data, self::BATCH_SIZE);

    foreach ($chunks as $index => $chunk) {
         TransationJob ::dispatch($chunk, $userId, $batchId)
            ->onQueue('default')
            ->delay(Carbon::now()->addSeconds($index * 10));
    }
        MonitorCustomerActivity::dispatch();
    return $batchId;
}

}
