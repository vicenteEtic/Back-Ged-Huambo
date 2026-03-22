<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Jobs\ProcessCustomerPoliciesJob;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispatchCustomerJobsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 10800; // 3h
    public $tries = 3;       // Tenta 3 vezes antes de falhar

    public function handle()
    {
        Log::info("🚀 Iniciando dispatch de jobs por cliente...");

        // Processa a tabela em chunks para evitar estouro de memória
        DB::table('policies_staging')
            ->select('*')
            ->orderBy('numero_cliente')
            ->chunk(500, function ($rows) {

                // Agrupa as apólices por cliente
                $grouped = $rows->groupBy('numero_cliente');

                foreach ($grouped as $numero_cliente => $policies) {

                    // Busca o cliente correto na tabela entities
                    $entity = Entities::where('customer_number', $numero_cliente)->first();

                    if (!$entity) {
                        Log::warning("Cliente não encontrado: {$numero_cliente}");
                        continue;
                    }

                    // Converte o chunk para array simples antes de enviar para o job
                    $policiesArray = $policies->map(function ($row) {
                        return [
                            'numero_apolice'    => $row->numero_apolice,
                            'descricao_produto' => $row->descricao_produto,
                            'estado_apolice'    => $row->estado_apolice,
                            'data_inicio'       => $row->data_inicio,
                            'data_fim'          => $row->data_fim,
                            'capital'           => $row->capital,
                            'premium_total'     => $row->premium_total,
                            'interest'          => $row->interest,
                        ];
                    })->toArray();

                    // Dispara o job de processamento das apólices
                    ProcessCustomerPoliciesJob::dispatch($entity->id, $policiesArray);

                    Log::info("📬 Job disparado para cliente {$numero_cliente} com " . count($policiesArray) . " apólices.");
                }
            });

        Log::info("✅ Todos os jobs disparados com sucesso.");
    }
}