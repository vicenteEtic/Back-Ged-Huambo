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

    public $timeout = 10800; // 3 horas
    public $tries = 10;      // Máximo de tentativas

    public function handle()
    {
        Log::info("🚀 Iniciando dispatch de jobs por cliente...");

        DB::table('policies_staging')
            ->select('numero_cliente')
            ->distinct()
            ->orderBy('numero_cliente')
            ->chunk(500, function ($clientes) {

                foreach ($clientes as $clienteRow) {
                    try {
                        $numero_cliente = $clienteRow->numero_cliente;

                        // Buscar entidade
                        $entity = Entities::where('customer_number', $numero_cliente)->first();

                        if (!$entity) {
                            Log::warning("⚠ Cliente não encontrado: {$numero_cliente}");
                            continue;
                        }

                        // Buscar todas as apólices do cliente
                        $policies = DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->get();

                        if ($policies->isEmpty()) {
                            Log::info("ℹ Cliente {$numero_cliente} não possui apólices.");
                            continue;
                        }

                        // Mapear apólices
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

                        // Disparar job individual por cliente
                        ProcessCustomerPoliciesJob::dispatch($entity->id, $policiesArray)
                            ->onQueue('high');

                        Log::info("📬 Job disparado para cliente {$numero_cliente} com " . count($policiesArray) . " apólices.");

                    } catch (\Exception $e) {
                        Log::error("❌ Erro processando cliente {$clienteRow->numero_cliente}: {$e->getMessage()}");
                        continue; // continua com o próximo cliente
                    }
                }

                // Limpeza de memória
                gc_collect_cycles();
            });

        Log::info("✅ Todos os jobs disparados com sucesso.");
    }
}