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

    public function handle()
    {
        Log::info("🚀 Iniciando dispatch de jobs por cliente...");

        $customers = DB::table('policies_staging')
            ->select('numero_cliente', DB::raw('JSON_ARRAYAGG(JSON_OBJECT(
                "numero_apolice", numero_apolice,
                "descricao_produto", descricao_produto,
                "estado_apolice", estado_apolice,
                "data_inicio", data_inicio,
                "data_fim", data_fim,
                "capital", capital,
                "premium_total", premium_total,
                "interest", interest
            )) as policies'))
            ->groupBy('numero_cliente')
            ->cursor();

        foreach ($customers as $customer) {
            $policies = json_decode($customer->policies, true);
            $entity = Entities::where('customer_number', $customer->customer_number)->first();
            if (!$entity) {
                Log::warning("Cliente não encontrado: {$customer->customer_number}");
                continue;
            }

            ProcessCustomerPoliciesJob::dispatch($entity->id, $policies);
            Log::info("📬 Job disparado para cliente {$customer->customer_number} com " . count($policies) . " apólices.");
        }

        Log::info("✅ Todos os jobs disparados");
    }
}