<?php

namespace App\Jobs;

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

        try {
            $batch = [];
            $batchSize = 50;

            DB::table('policies_staging')
                ->select('numero_cliente')
                ->distinct()
                ->orderBy('numero_cliente')
                ->chunk(200, function ($clientes) use (&$batch, $batchSize) {
                    foreach ($clientes as $cliente) {
                        if (!empty($cliente->numero_cliente)) {
                            $batch[] = $cliente->numero_cliente;

                            if (count($batch) >= $batchSize) {
                                ProcessCustomerDataJob::dispatch($batch)
                                    ->onQueue('cliente');
                                $batch = [];
                            }
                        }
                    }
                });

            if (!empty($batch)) {
                ProcessCustomerDataJob::dispatch($batch)
                    ->onQueue('cliente');
            }

            Log::info("✅ Todos os clientes foram enfileirados com sucesso.");
        } catch (\Throwable $e) {
            Log::error("❌ Erro no dispatch de clientes", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}