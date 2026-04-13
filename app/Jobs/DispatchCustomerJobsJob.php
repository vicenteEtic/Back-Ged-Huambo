<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
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
        ->chunkById(200, function ($clientes) {

            foreach ($clientes as $clienteRow) {

                try {
                    $numero_cliente = $clienteRow->numero_cliente;

                    $entity = Entities::where('customer_number', $numero_cliente)->first();
                    if (!$entity) continue;

                    // 🔹 POLICIES (STREAM em vez de get massivo)
                    $policiesRaw = DB::table('policies_staging')
                        ->where('numero_cliente', $numero_cliente)
                        ->limit(5000) // 🔥 PROTEÇÃO MEMÓRIA
                        ->get();

                    if ($policiesRaw->isEmpty()) continue;

                    $policiesArray = $policiesRaw->map(fn($i) => (array)$i)->toArray();

                    $policyNumbers = $policiesRaw
                        ->pluck('numero_apolice')
                        ->filter()
                        ->unique()
                        ->values()
                        ->toArray();

                    if (empty($policyNumbers)) continue;

                    // 🔹 RECEIPTS (LIMITADO)
                    $receipts = DB::table('recibos_cobrados')
                        ->select([
                            'numero_apolice',
                            'data_pagamento',
                            'valor_pago',
                            'nome_pagador',
                            'nif_pagador',
                            'relacao_com_tomador',
                            'indicador_pagamento_terceiro',
                            'pais_iban_origem'
                        ])
                        ->whereIn('numero_apolice', $policyNumbers)
                        ->limit(5000)
                        ->get()
                        ->map(fn($i) => (array)$i)
                        ->toArray();

                    // 🔹 CHANGES (LIMITADO)
                    $changes = DB::table('policy_changes_staging')
                        ->whereIn('numero_apolice', $policyNumbers)
                        ->limit(3000)
                        ->get()
                        ->map(fn($i) => (array)$i)
                        ->toArray();

                    // 🔹 REFUNDS
                    $refunds = DB::table('apol_anulada_estorno')
                        ->where('idtitular', (string)$numero_cliente)
                        ->limit(2000)
                        ->get()
                        ->map(fn($i) => (array)$i)
                        ->toArray();

                    ProcessCustomerPoliciesJob::dispatch(
                        $entity->id,
                        $policiesArray,
                        $changes,
                        $refunds,
                        $receipts
                    )->onQueue('cliente');

                } catch (\Throwable $e) {
                    Log::error("Erro cliente {$clienteRow->numero_cliente}: {$e->getMessage()}");
                }
            }
        });

    Log::info("✅ Dispatch concluído.");
}
}