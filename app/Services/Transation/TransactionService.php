<?php

namespace App\Services\Transation;

use App\Jobs\MonitorCustomerActivity;
use App\Repositories\Transation\TransactionRepository;
use App\Services\AbstractService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use App\Jobs\TransationJob;

class TransactionService extends AbstractService
{


    private const BATCH_SIZE = 8000;
    private const TIME_LIMIT_SECONDS = 10;
    public function __construct(TransactionRepository $repository)
    {
        parent::__construct($repository);
    }
public function dispatchImportJobs(array $data, $userId): string
{
    $batchId = Str::uuid()->toString();

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
