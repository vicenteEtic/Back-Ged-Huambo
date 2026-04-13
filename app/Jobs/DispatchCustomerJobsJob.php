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
            ->chunk(200, function ($clientes) {

                foreach ($clientes as $clienteRow) {

                    try {
                        $numero_cliente = $clienteRow->numero_cliente;

                        $entity = Entities::where('customer_number', $numero_cliente)->first();
                        if (!$entity) continue;

                        /**
                         * 🔥 POLICIES (LIMIT + STREAM SAFE)
                         */
                        $policiesRaw = DB::table('policies_staging')
                            ->where('numero_cliente', $numero_cliente)
                            ->limit(2000)
                            ->get();

                        if ($policiesRaw->isEmpty()) continue;

                        $policiesArray = $policiesRaw->toArray();

                        $policyNumbers = $policiesRaw
                            ->pluck('numero_apolice')
                            ->filter()
                            ->unique()
                            ->values()
                            ->all();

                        if (empty($policyNumbers)) continue;

                        /**
                         * 🔥 RECEIPTS (PROTEGIDO)
                         */
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
                            ->limit(2000)
                            ->get()
                            ->toArray();

                        /**
                         * 🔥 CHANGES (LIMITADO)
                         */
                        $changes = DB::table('policy_changes_staging')
                            ->whereIn('numero_apolice', $policyNumbers)
                            ->limit(1500)
                            ->get()
                            ->toArray();

                        /**
                         * 🔥 REFUNDS (LIMITADO)
                         */
                        $refunds = DB::table('apol_anulada_estorno')
                            ->where('idtitular', (string)$numero_cliente)
                            ->limit(1000)
                            ->get()
                            ->toArray();

                        $beneficiaries = DB::table('beneficiarios_staging as b')
                            ->join('policies_staging as p', 'b.numero_apolice', '=', 'p.numero_apolice')
                            ->where('p.numero_apolice', $policyNumbers)
                            ->select([
                                'b.numero_apolice',
                                'p.descricao_produto',
                                'b.codigo_beneficiario',
                                'b.nome_beneficiario',
                                'b.tipo_beneficiario',
                                'b.percentagem_atribuida',
                                'b.data_atualizacao_beneficiario'
                            ])
                            ->get()
                            ->toArray();
                        /**
                         * 🚀 DISPATCH
                         */
                        ProcessCustomerPoliciesJob::dispatch(
                            $entity->id,
                            $policiesArray,
                            $changes,
                            $refunds,
                            $receipts,
                            $beneficiaries
                        )->onQueue('cliente');

                        unset($policiesRaw, $receipts, $changes, $refunds, $policyNumbers);

                        gc_collect_cycles();
                    } catch (\Throwable $e) {
                        Log::error("❌ Cliente {$clienteRow->numero_cliente}: {$e->getMessage()}");
                    }
                }
            });

        Log::info("✅ Dispatch concluído.");
    }
}
