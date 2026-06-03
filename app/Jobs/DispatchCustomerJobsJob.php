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
            $lastCliente = 0;

            do {
                $clientes = DB::table('policies_staging')
                    ->select('numero_cliente')
                    ->where('numero_cliente', '>', $lastCliente)
                    ->distinct()
                    ->orderBy('numero_cliente')
                    ->limit(200)
                    ->get();

                foreach ($clientes as $cliente) {
                    if (!empty($cliente->numero_cliente)) {
                        ProcessCustomerDataJob::dispatch($cliente->numero_cliente)
                            ->onQueue('cliente');
                    }
                }

                $lastCliente = $clientes->isNotEmpty()
                    ? (int) $clientes->last()->numero_cliente
                    : 0;
            } while ($clientes->count() > 0);

            Log::info("✅ Todos os clientes foram enfileirados com sucesso.");

        } catch (\Throwable $e) {

            Log::error("❌ Erro no dispatch de clientes", [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }
}