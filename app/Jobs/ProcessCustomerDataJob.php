<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessCustomerDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 3;

    /** @var string|string[] */
    protected array|string $numeroCliente;

    public function __construct(array|string $numeroCliente)
    {
        $this->numeroCliente = $numeroCliente;
    }

    public function handle()
    {
        $clientes = is_array($this->numeroCliente)
            ? $this->numeroCliente
            : [$this->numeroCliente];

        foreach ($clientes as $numCliente) {
            $this->processCliente($numCliente);
        }
    }

    private function processCliente(string $numeroCliente): void
    {
        Log::info("🚀 Cliente: {$numeroCliente}");

        try {
            $entity = Entities::where('customer_number', $numeroCliente)->first();
        } catch (\Throwable $e) {
            Log::error("❌ Erro ao buscar entidade para cliente {$numeroCliente}", [
                'message' => $e->getMessage(),
            ]);
            return;
        }

        if (!$entity) {
            Log::warning("⚠️ Cliente não encontrado: {$numeroCliente}");
            return;
        }

        $kytJobs = [];

        try {
            DB::table('policies_staging')
                ->where('numero_cliente', $numeroCliente)
                ->chunkById(500, function ($policies) use ($entity, &$kytJobs, $numeroCliente) {

                    $policyNumbers = $policies->pluck('numero_apolice')
                        ->filter()
                        ->unique()
                        ->values()
                        ->all();

                    if (empty($policyNumbers)) return;

                    try {
                        $changes = DB::table('policy_changes_staging')
                            ->whereIn('numero_apolice', $policyNumbers)->get()->toArray();
                        $refunds = DB::table('apol_anulada_estorno')
                            ->whereIn('n_apolice', $policyNumbers)->get()->toArray();
                        $receipts = DB::table('recibos_cobrados')
                            ->whereIn('numero_apolice', $policyNumbers)->get()->toArray();
                        $beneficiaries = DB::table('beneficiarios_staging')
                            ->whereIn('numero_apolice', $policyNumbers)->get()->toArray();
                    } catch (\Throwable $e) {
                        Log::error("❌ Erro na pré-busca de dados para cliente {$numeroCliente}", [
                            'policies' => $policyNumbers,
                            'message' => $e->getMessage(),
                        ]);
                        throw $e;
                    }

                    $kytJobs[] = new ProcessCustomerPoliciesJob(
                        $entity->id,
                        $policyNumbers,
                        $policies->toArray(),
                        $changes,
                        $refunds,
                        $receipts,
                        $beneficiaries
                    );
                }, 'id');
        } catch (\Throwable $e) {
            Log::error("❌ Erro no chunkById para cliente {$numeroCliente}", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }

        if (!empty($kytJobs)) {
            try {
                Bus::batch($kytJobs)
                    ->onQueue('cliente')
                    ->allowFailures()
                    ->finally(function (Batch $batch) use ($numeroCliente) {
                        Log::info("✅ KYT batch concluído para cliente {$numeroCliente}", [
                            'total' => $batch->totalJobs,
                            'failed' => $batch->failedJobs,
                        ]);
                    })
                    ->dispatch();
            } catch (\Throwable $e) {
                Log::error("❌ Erro ao despachar batch KYT para cliente {$numeroCliente}", [
                    'message' => $e->getMessage(),
                    'trace' => $e->getTraceAsString(),
                ]);
                throw $e;
            }
        } else {
            Log::warning("⚠️ Nenhum job KYT gerado para cliente {$numeroCliente}");
        }
    }
}