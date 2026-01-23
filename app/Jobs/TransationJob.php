<?php

namespace App\Jobs;

use App\Models\Transation\transaionControl;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Bus\Batchable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class TransationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

     protected array $data;
    protected int $userID;
    protected int $controlId;   // <-- novo

    public $tries = 5;
    public $timeout = 36000;

    public function __construct(array $data, int $userID, int $controlId)
    {
        $this->data = $data;
        $this->userID = $userID;
        $this->controlId = $controlId;
    }


    public function handle(): void
    {
       
        $controlId = $this->controlId; // seu id de controle

        Log::info('🚀 TransationJob iniciado', [
            'batch_id' => $controlId,
            'user_id'  => $this->userID,
            'records'  => count($this->data),
        ]);

        $batchSize = 1000;
        $chunks = array_chunk($this->data, $batchSize);

        $insertedCount = 0;

        foreach ($chunks as $chunkIndex => $records) {
            DB::beginTransaction();

            try {
                $policiesData = [];

                foreach ($records as $record) {
                    if (
                        empty($record['contract_number']) ||
                        empty($record['nif']) ||
                        empty($record['customer_number'])
                    ) {
                        continue;
                    }

                    DB::table('entities')->updateOrInsert(
                        ['customer_number' => $record['customer_number']],
                        [
                            'nif'                 => $record['nif'],
                            'policy_number'       => $record['policy_number'] ?? 'UNKNOWN',
                            'social_denomination' => $record['social_denomination'] ?? 'UNKNOWN',
                            'updated_at'          => now(),
                            'created_at'          => now(),
                        ]
                    );

                    $entityId = DB::table('entities')
                        ->where('customer_number', $record['customer_number'])
                        ->value('id');

                    if (!$entityId) {
                        continue;
                    }

                    $policiesData[] = [
                        'entity_id'       => $entityId,
                         'control_id'      => $controlId,
                        'contract_number' => $record['contract_number'],
                        'product'         => $record['product'] ?? null,
                        'channel'         => $record['channel'] ?? null,
                        'agent'           => $record['agent'] ?? null,
                        'start_date'      => !empty($record['start_date']) ? Carbon::parse($record['start_date']) : null,
                        'end_date'        => !empty($record['end_date']) ? Carbon::parse($record['end_date']) : null,
                        'issue_date'      => !empty($record['issue_date']) ? Carbon::parse($record['issue_date']) : null,
                        'renewal_date'    => !empty($record['renewal_date']) ? Carbon::parse($record['renewal_date']) : null,
                        'capital'         => $record['capital'] ?? 0,
                        'premium_simple'  => $record['premium_simple'] ?? 0,
                        'premium_total'   => $record['premium_total'] ?? 0,
                        'charges'         => $record['charges'] ?? 0,
                        'interest'        => $record['interest'] ?? 0,
                        'status'          => $record['status'] ?? 'active',
                        'created_at'      => now(),
                        'updated_at'      => now(),
                    ];
                }

                if (!empty($policiesData)) {
                    DB::table('policies')->insert($policiesData);
                    $insertedCount += count($policiesData);
                }

                DB::commit();

                Log::info('✅ Chunk processado', [
                    'batch_id'       => $controlId,
                    'chunk'          => $chunkIndex,
                    'inserted_chunk' => count($policiesData),
                    'inserted_total' => $insertedCount,
                ]);

            } catch (\Throwable $e) {
                DB::rollBack();

                Log::error('❌ Erro no chunk', [
                    'batch_id' => $controlId,
                    'chunk'    => $chunkIndex,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->incrementSuccessCount($insertedCount,  $controlId );

        Log::info('🏁 TransationJob finalizado', [
            'batch_id'           =>  $controlId ,
            'total_inserted_job' => $insertedCount,
        ]);
    }

    private function incrementSuccessCount(int $count,   $controlId): void
    {
        if ($count <= 0 || !$controlId) {
            return;
        }

        transaionControl::where('id',  $controlId)
            ->increment('total', $count);
    }
}
