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

                        if (!$entity) continue;

                        // 1. Buscamos os dados puros primeiro para facilitar manipulação
                        $policiesRaw = DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->get();

                        if ($policiesRaw->isEmpty()) continue;

                        // 2. Extraímos os números das apólices ANTES de converter para array
                        $policyNumbers = $policiesRaw->pluck('numero_apolice')->toArray();

                        // 3. Convertemos tudo para array para evitar erro de stdClass no Service
                        $policiesArray = $policiesRaw->map(fn($item) => (array) $item)->toArray();

                        $refunds = DB::table('apol_anulada_estorno')
                            ->where('idtitular', (string)$numero_cliente)
                            ->get()
                            ->map(fn($item) => (array) $item)
                            ->toArray();

                        $changes = DB::table('policy_changes_staging')
                            ->whereIn('numero_apolice', $policyNumbers) // 🔥 Mais seguro
                            ->get()
                            ->map(fn($item) => (array) $item)
                            ->toArray();

                        // 4. Dispatch
                        ProcessCustomerPoliciesJob::dispatch(
                            $entity->id,
                            $policiesArray,
                            $changes,
                            $refunds 
                        )->onQueue('cliente');

                    } catch (\Exception $e) {
                        Log::error("❌ Erro cliente {$clienteRow->numero_cliente}: {$e->getMessage()}");
                    }
                }
                gc_collect_cycles();
            });
            
        Log::info("✅ Dispatch concluído.");
    }
}