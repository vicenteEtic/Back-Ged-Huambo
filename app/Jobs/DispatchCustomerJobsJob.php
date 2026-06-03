<?php

namespace App\Jobs;

use Illuminate\Bus\Batch;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Bus;
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

        try {
            $jobs = [];
            $batch = [];
            $batchSize = 50;

            DB::table('policies_staging')
                ->select('numero_cliente')
                ->distinct()
                ->orderBy('numero_cliente')
                ->chunk(200, function ($clientes) use (&$jobs, &$batch, $batchSize) {
                    foreach ($clientes as $cliente) {
                        if (!empty($cliente->numero_cliente)) {
                            $batch[] = $cliente->numero_cliente;

                            if (count($batch) >= $batchSize) {
                                $jobs[] = new ProcessCustomerDataJob($batch);
                                $batch = [];
                            }
                        }
                    }
                });

            if (!empty($batch)) {
                $jobs[] = new ProcessCustomerDataJob($batch);
            }

            /**
             * 🚀 Dispatch atómico: todos os jobs vão para a queue de uma só vez.
             * Nenhum worker pega jobs antes do batch estar completo.
             */
            Bus::batch($jobs)
                ->onQueue('cliente')
                ->allowFailures()
                ->finally(function (Batch $batch) {
                    Log::info("✅ Batch de clientes concluído", [
                        'total' => $batch->totalJobs,
                        'failed' => $batch->failedJobs,
                    ]);
                })
                ->dispatch();

            Log::info("✅ Todos os clientes foram enfileirados via batch.");
        } catch (\Throwable $e) {
            Log::error("❌ Erro no dispatch de clientes", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}