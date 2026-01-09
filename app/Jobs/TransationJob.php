<?php

namespace App\Jobs;

use App\Models\Transation\transaionControl;
use App\Services\Entities\EntitiesService;
use App\Services\Transation\PoliciesService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class TransationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected array $data;
    protected int $userID;
    protected $batchId;

    public $tries = 5;
    public $timeout = 36000;

    public function __construct(array $data, int $userID, $batchId)
    {
        $this->data = $data;
        $this->userID = $userID;
        $this->batchId = $batchId;
    }

    public function handle(): void
    {
        $policieService = app(PoliciesService::class);
        $entityService = app(EntitiesService::class);

        foreach ($this->data as $record) {
            DB::beginTransaction();
            try {
                $entity = $entityService->storeOrUpdate(
                    ['nif' => $record['nif'] ?? 'UNKNOWN'],
                    [
                        'policy_number' => $record['policy_number'] ?? 'UNKNOWN',
                        'customer_number' => $record['customer_number'] ?? 'UNKNOWN',
                        'nif' => $record['nif'] ?? 'UNKNOWN',
                        'social_denomination' => $record['social_denomination'] ?? 'UNKNOWN',
                    ]
                );

                $policieService->store([

                    
                    'control_id' => $this->batchId,
                    'entity_id' => $entity->id,
                    'contract_number' => $record['contract_number'],
                    'product' => $record['product'],
                    'channel' => $record['channel'],
                    'agent' => $record['agent'],
                    'start_date' => $record['start_date'],
                    'end_date' => $record['end_date'],
                    'issue_date' => $record['issue_date'],
                    'renewal_date' => $record['renewal_date'],
                    'capital' => $record['capital'],
                    'premium_simple' => $record['premium_simple'],
                    'premium_total' => $record['premium_total'],
                    'charges' => $record['charges'],
                    'interest' => $record['interest'],
                    'status' => $record['status'],
                ]);
                  $this->incrementSuccessCount();

                DB::commit();
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error('Erro no processamento do registro', [
                    'record' => $record,
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
            }
        }



        

    }

      private function incrementSuccessCount()
    {
        $errorRecord = transaionControl::find($this->batchId);

        if ($errorRecord) {
            $errorRecord->update([
                'total' => $errorRecord->total + 1,
            ]);
        }
    }
}
