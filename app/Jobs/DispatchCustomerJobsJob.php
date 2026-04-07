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
    public $tries = 10;

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

                        $entity = Entities::where('customer_number', $numero_cliente)->first();

                        if (!$entity) {
                            Log::warning("⚠ Cliente não encontrado: {$numero_cliente}");
                            continue;
                        }

                        // 🔹 Apólices
                        $policies = DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->get();

                        if ($policies->isEmpty()) continue;

                        $policiesArray = $policies->map(fn($row) => [
                            'numero_apolice' => $row->numero_apolice,
                            'descricao_produto' => $row->descricao_produto,
                            'estado_apolice' => $row->estado_apolice,
                            'data_inicio' => $row->data_inicio,
                            'data_fim' => $row->data_fim,
                            'capital' => $row->capital,
                            'premium_total' => $row->premium_total,
                            'interest' => $row->interest,
                        ])->toArray();

                        // 🔥 ALTERAÇÕES (NOVO)
                        $changes = DB::table('policy_changes_staging')
                            ->whereIn('numero_apolice', $policies->pluck('numero_apolice'))
                            ->get()
                            ->toArray();

                        // 🔥 DISPATCH COMPLETO
                        ProcessCustomerPoliciesJob::dispatch(
                            $entity->id,
                            $policiesArray,
                            $changes
                        )->onQueue('cliente');

                        Log::info("📬 Cliente {$numero_cliente} enviado com " . count($policiesArray) . " apólices e " . count($changes) . " alterações");

                    } catch (\Exception $e) {
                        Log::error("❌ Erro cliente {$clienteRow->numero_cliente}: {$e->getMessage()}");
                    }
                }

                gc_collect_cycles();
            });

        Log::info("✅ Dispatch concluído.");
    }
}