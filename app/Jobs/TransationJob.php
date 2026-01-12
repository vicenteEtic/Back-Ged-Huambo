<?php

namespace App\Jobs;

use App\Models\Transation\transaionControl;
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
        'user_id' => $this->userID,
        'total_records' => count($this->data),
    ]);

    $batchSize = 1000;
    $chunks = array_chunk($this->data, $batchSize);
    $insertedCount = 0; // contador local do job

    foreach ($chunks as $chunkIndex => $records) {
        DB::beginTransaction();
        try {
            $policiesData = [];

            foreach ($records as $record) {
                if (empty($record['contract_number']) || empty($record['nif'])) {
                    continue;
                }

                DB::table('entities')->updateOrInsert(
                    ['customer_number' => $record['customer_number']],
                    [
                        'nif' => $record['nif'],
                        'policy_number' => $record['policy_number'] ?? 'UNKNOWN',
                        'social_denomination' => $record['social_denomination'] ?? 'UNKNOWN',
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]
                );

                $entityId = DB::table('entities')
                    ->where('customer_number', $record['customer_number'])
                    ->value('id');

                $policiesData[] = [
                    'entity_id' => $entityId,
                    'control_id' => $this->batchId,
                    'contract_number' => $record['contract_number'],
                    'product' => $record['product'] ?? null,
                    'channel' => $record['channel'] ?? null,
                    'agent' => $record['agent'] ?? null,
                    'start_date' => $record['start_date'] ? Carbon::parse($record['start_date']) : null,
                    'end_date' => $record['end_date'] ? Carbon::parse($record['end_date']) : null,
                    'issue_date' => $record['issue_date'] ? Carbon::parse($record['issue_date']) : null,
                    'renewal_date' => $record['renewal_date'] ? Carbon::parse($record['renewal_date']) : null,
                    'capital' => $record['capital'] ?? 0,
                    'premium_simple' => $record['premium_simple'] ?? 0,
                    'premium_total' => $record['premium_total'] ?? 0,
                    'charges' => $record['charges'] ?? 0,
                    'interest' => $record['interest'] ?? 0,
                    'status' => $record['status'] ?? 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            if (!empty($policiesData)) {
                DB::table('policies')->insert($policiesData);
                $insertedCount += count($policiesData); // acumula localmente
            }

            DB::commit();

            Log::info("✅ Lote $chunkIndex processado com sucesso", [
                'batch_id' => $this->batchId,
                'records_in_chunk' => count($records),
                'inserted_in_chunk' => count($policiesData),
                'inserted_so_far_in_job' => $insertedCount,
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('❌ Erro no lote', [
                'batch_id' => $this->batchId,
                'chunk_index' => $chunkIndex,
                'error' => $e->getMessage(),
            ]);
        }
    }

    // Atualiza total de registros inseridos **uma única vez** no final do job
    $this->incrementSuccessCount($insertedCount);

    Log::info('🏁 TransationJob finalizado', [
        'batch_id' => $this->batchId,
        'total_inserted_by_job' => $insertedCount,
    ]);
}

private function incrementSuccessCount(int $count)
{
    $record = transaionControl::find($this->batchId);
    if ($record && $count > 0) {
        $record->increment('total', $count); // usa increment direto no banco, mais rápido
    }
}


}
