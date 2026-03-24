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

    public $timeout = 10800;
    public $tries = 3; // evita loop infinito

    public function handle()
    {
        Log::info("🚀 Iniciando dispatch de jobs por cliente...");

        // Pegando clientes únicos em chunks
        DB::table('policies_staging')
            ->select('numero_cliente')
            ->distinct()
            ->orderBy('numero_cliente')
            ->chunk(200, function ($clientes) {

                foreach ($clientes as $clienteRow) {

                    try {
                        $numero_cliente = $clienteRow->numero_cliente;

                        // Buscar entidade
                        $entity = Entities::where('customer_number', $numero_cliente)->first();
                        if (!$entity) {
                            Log::warning("Cliente não encontrado: {$numero_cliente}");
                            continue;
                        }

                        $policiesArray = [];

                        // 🔥 Buscar TODAS as apólices do cliente em chunks (evita memória alta)
                        DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->orderBy('data_inicio') // garante ordem cronológica
                            ->chunk(5000, function ($rows) use (&$policiesArray) {
                                foreach ($rows as $row) {
                                    $policiesArray[] = [
                                        'numero_apolice'    => $row->numero_apolice,
                                        'numero_cliente'    => $row->numero_cliente,
                                        'descricao_produto' => $row->descricao_produto,
                                        'estado_apolice'    => $row->estado_apolice,
                                        'data_inicio'       => $row->data_inicio,
                                        'data_fim'          => $row->data_fim,
                                        'capital'           => $row->capital,
                                        'premium_total'     => $row->premium_total,
                                        'interest'          => $row->interest,
                                    ];
                                }
                            });

                        if (empty($policiesArray)) {
                            Log::info("Nenhuma apólice encontrada para {$numero_cliente}");
                            continue;
                        }

                        // 🚀 Envia todas as apólices do cliente para o KYT
                        ProcessCustomerPoliciesJob::dispatch($entity->id, $policiesArray)
                            ->onQueue('high');

                        Log::info("📬 Job enviado para cliente {$numero_cliente} com " . count($policiesArray) . " apólices.");

                    } catch (\Throwable $e) {
                        Log::error("❌ Erro ao processar cliente {$clienteRow->numero_cliente}", [
                            'error' => $e->getMessage(),
                            'line' => $e->getLine(),
                        ]);
                        continue;
                    }
                }

                // Libera memória
                gc_collect_cycles();
            });

        Log::info("✅ Todos os jobs disparados com sucesso.");
    }
}