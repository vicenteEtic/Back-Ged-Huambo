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

                        $policies = DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->get();

                        if ($policies->isEmpty()) continue;

                        // 🔥 BUSCAR ANULAÇÕES/ESTORNOS DO CLIENTE
                        $refunds = DB::table('apol_anulada_estorno')
                            ->where('idtitular', (string)$numero_cliente)
                            ->get()
                            ->toArray();

                        $changes = DB::table('policy_changes_staging')
                            ->whereIn('numero_apolice', $policies->pluck('numero_apolice'))
                            ->get()
                            ->toArray();

                        // 🔥 PASSA O NOVO ARRAY DE REFUNDS
                        ProcessCustomerPoliciesJob::dispatch(
                            $entity->id,
                            $policies->toArray(),
                            $changes,
                            $refunds 
                        )->onQueue('cliente');

                    } catch (\Exception $e) {
                        Log::error("❌ Erro cliente {$clienteRow->numero_cliente}: {$e->getMessage()}");
                    }
                }
                gc_collect_cycles();
            });
    }
}