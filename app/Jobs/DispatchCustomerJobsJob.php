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
            ->chunk(500, function ($clientes) {

                foreach ($clientes as $clienteRow) {
                    try {
                        $numero_cliente = $clienteRow->numero_cliente;

                        $entity = Entities::where('customer_number', $numero_cliente)->first();
                        if (!$entity) continue;

                        // 🔹 POLICIES
                        $policiesRaw = DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->get();

                        if ($policiesRaw->isEmpty()) continue;

                        $policiesArray = $policiesRaw->map(fn($i) => (array)$i)->toArray();

                        // 🔹 POLICY NUMBERS
                        $policyNumbers = array_column($policiesArray, 'Numero_Apolice');

                        // 🔹 REFUNDS
                        $refundsRaw = DB::table('apol_anulada_estorno')
                            ->where('idtitular', (string)$numero_cliente)
                            ->get();

                        $refunds = $refundsRaw->map(fn($i) => (array)$i)->toArray();

                        // 🔹 RECEIPTS (🔥 NOVO)
                        $receiptsRaw = DB::table('recibos_cobrados')
                            ->whereIn('numero_apolice', $policyNumbers)
                            ->get();

                        $receipts = $receiptsRaw->map(fn($i) => (array)$i)->toArray();

                        // 🔹 CHANGES
                        $changesRaw = DB::table('policy_changes_staging')
                            ->whereIn('numero_apolice', $policyNumbers)
                            ->get();

                        $changes = $changesRaw->map(fn($i) => (array)$i)->toArray();

                        // 🚀 DISPATCH
                        ProcessCustomerPoliciesJob::dispatch(
                            $entity->id,
                            $policiesArray,
                            $changes,
                            $refunds,
                            $receipts
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