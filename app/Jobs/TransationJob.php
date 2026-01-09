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
use Carbon\Carbon;

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
        Log::info('🚀 TransationJob iniciado', [
            'batch_id' => $this->batchId,
            'user_id'  => $this->userID,
            'total_records' => count($this->data),
        ]);
    
        $policieService = app(PoliciesService::class);
        $entityService  = app(EntitiesService::class);
    
        foreach ($this->data as $index => $record) {
    
            Log::debug('📄 Processando registo', [
                'batch_id' => $this->batchId,
                'index'    => $index,
                'nif'      => $record['nif'] ?? null,
                'contract' => $record['contract_number'] ?? null,
            ]);
    
            DB::beginTransaction();
    
            try {
    
                // Validação mínima defensiva
                if (empty($record['contract_number']) || empty($record['nif'])) {
                    Log::warning('⚠️ Registo inválido (dados obrigatórios ausentes)', [
                        'record' => $record
                    ]);
                    DB::rollBack();
                    continue;
                }
    
                $entity = $entityService->storeOrUpdate(
                    ['nif' => $record['nif']],
                    [
                        'policy_number'      => $record['policy_number'] ?? 'UNKNOWN',
                        'customer_number'    => $record['customer_number'] ?? 'UNKNOWN',
                        'nif'                => $record['nif'],
                        'social_denomination'=> $record['social_denomination'] ?? 'UNKNOWN',
                    ]
                );
    
                Log::debug('👤 Entidade criada/atualizada', [
                    'entity_id' => $entity->id,
                    'nif'       => $entity->nif,
                ]);
    
                $policieService->store([
                    'control_id'     => $this->batchId,
                    'entity_id'      => $entity->id,
                    'contract_number'=> $record['contract_number'],
                    'product'        => $record['product'],
                    'channel'        => $record['channel'],
                    'agent'          => $record['agent'],
                    'start_date'     => Carbon::parse($record['start_date']),
                    'end_date'       => Carbon::parse($record['end_date']),
                    'issue_date'     => Carbon::parse($record['issue_date']),
                    'renewal_date'   => Carbon::parse($record['renewal_date']),
                    'capital'        => $record['capital'],
                    'premium_simple' => $record['premium_simple'],
                    'premium_total'  => $record['premium_total'],
                    'charges'        => $record['charges'],
                    'interest'       => $record['interest'],
                    'status'         => $record['status'],
                ]);
    
                Log::info('✅ Apólice registada com sucesso', [
                    'batch_id' => $this->batchId,
                    'entity_id'=> $entity->id,
                    'contract' => $record['contract_number'],
                ]);
    
                $this->incrementSuccessCount();
    
                DB::commit();
    
            } catch (\Throwable $e) {
    
                DB::rollBack();
    
                Log::error('❌ Erro ao processar registo', [
                    'batch_id' => $this->batchId,
                    'record'   => $record,
                    'error'    => $e->getMessage(),
                    'file'     => $e->getFile(),
                    'line'     => $e->getLine(),
                ]);
            }
        }
    
        Log::info('🏁 TransationJob finalizado', [
            'batch_id' => $this->batchId
        ]);
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
