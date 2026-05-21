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

class TransationSolJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, Batchable;

    protected array $data;
    protected int $userID;
    protected int $controlId;

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
        $controlId = $this->controlId;
        $totalRecords = count($this->data);

        Log::info('🚀 TransationJob baseado no CSV iniciado', [
            'batch_id' => $controlId,
            'user_id'  => $this->userID,
            'records'  => $totalRecords,
        ]);

        $batchSize = 1500;
        $chunks = array_chunk($this->data, $batchSize);
        $insertedCount = 0;

        foreach ($chunks as $chunkIndex => $records) {
            DB::beginTransaction();

            try {
                $policiesData = [];

                foreach ($records as $record) {
                    $contrato = $record['Contrato'] ?? null;
                    $tomador  = $record['Tomador'] ?? null;

                    if (empty($contrato) || empty($tomador)) {
                        continue;
                    }

                    $telefone = !empty($record['Telemóvel']) ? $record['Telemóvel'] : ($record['Telefone'] ?? null);
                    $customerNumber = !empty($telefone) ? preg_replace('/[^0-9]/', '', $telefone) : substr(md5($tomador), 0, 9);
                    $nif = !empty($record['NIF']) ? $record['NIF'] : $customerNumber;

                    // 1. Sincroniza a tabela 'entities' (Idempotente)
                    DB::table('entities')->updateOrInsert(
                        ['customer_number' => $customerNumber],
                        [
                            'nif'                 => $nif,
                            'entity_type'         => 2,
                            'policy_number'       => $contrato,
                            'social_denomination' => $tomador,
                            'updated_at'          => now(),
                            'created_at'          => now(),
                        ]
                    );

                    $entityId = DB::table('entities')
                        ->where('customer_number', $customerNumber)
                        ->value('id');

                    if (!$entityId) {
                        continue;
                    }

                    $parseCurrency = function ($value) {
                        if (empty($value)) return 0;
                        $value = str_replace(' ', '', $value); 
                        $value = str_replace(',', '.', $value); 
                        return (float) $value;
                    };

                    // 2. Coleta estruturada dos dados
                    $policiesData[] = [
                        'contract_number'   => $contrato,
                        'product_code'      => null, 
                        'product_desc'      => $record['Produto'] ?? null,
                        'branch_code'       => null,
                        'branch_desc'       => null,
                        'channel_code'      => null,
                        'channel_desc'      => $record['Canal'] ?? null,
                        'agent_code'        => null,
                        'agent_desc'        => $record['Agente produtor'] ?? null,
                        'status'            => $record['Situação'] ?? ($record['Estado'] ?? 'Em vigor'),
                        'start_date'        => !empty($record['Data início']) ? Carbon::parse($record['Data início'])->format('Y-m-d') : null,
                        'end_date'          => !empty($record['Data fim']) ? Carbon::parse($record['Data fim'])->format('Y-m-d') : null,
                        'next_renewal_date' => !empty($record['Data renovação']) ? Carbon::parse($record['Data renovação'])->format('Y-m-d') : null,
                        'next_expiry_date'  => null, 
                        'currency'          => 'AOA', 
                        'capital'           => $parseCurrency($record['Capital'] ?? 0),
                        'capital_cosign'    => null,
                        'premium_simple'    => $parseCurrency($record['Prémio simples'] ?? 0),
                        'premium_total'     => $parseCurrency($record['Prémio total'] ?? 0),
                        'charges'           => $parseCurrency($record['Encargos'] ?? 0),
                        'other_charges'     => null,
                        'interest'          => $parseCurrency($record['Juros fraccionamento'] ?? 0),
                        'created_at'        => now(),
                        'updated_at'        => now(),
                    ];
                }

                // 3. O SEGREDO DA IDEMPOTÊNCIA E PERFORMANCE: UPSERT
                if (!empty($policiesData)) {
                    DB::table('policies')->upsert(
                        $policiesData, 
                        ['contract_number'], // Coluna identificadora única (Unique Key)
                        [
                            'product_desc', 'channel_desc', 'agent_desc', 'status', 
                            'start_date', 'end_date', 'next_renewal_date', 'capital', 
                            'premium_simple', 'premium_total', 'charges', 'interest', 'updated_at'
                        ] // Colunas que devem ser atualizadas caso o contrato já exista
                    );
                    
                    $insertedCount += count($policiesData);
                }

                DB::commit();

                Log::info('📊 Progresso do Lote: 1500 registros sincronizados', [
                    'batch_id'          => $controlId,
                    'lote_atual'        => $chunkIndex + 1,
                    'total_lotes'       => count($chunks),
                    'processados_lote'  => count($policiesData),
                    'total_processado'  => $insertedCount,
                    'total_geral'       => $totalRecords
                ]);
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('❌ Erro no processamento do lote do CSV', [
                    'batch_id' => $controlId,
                    'chunk'    => $chunkIndex,
                    'error'    => $e->getMessage(),
                ]);
            }
        }

        $this->incrementSuccessCount($insertedCount, $controlId);

        Log::info('🏁 TransationJob finalizado com sucesso', [
            'batch_id'           => $controlId,
            'total_inserted_job' => $insertedCount,
        ]);
    }

    private function incrementSuccessCount(int $count, $controlId): void
    {
        if ($count <= 0 || !$controlId) {
            return;
        }

        transaionControl::where('id', $controlId)
            ->increment('total', $count);
    }
}