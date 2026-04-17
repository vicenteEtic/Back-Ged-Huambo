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


        try {

            DB::table('policies_staging')
                ->select('numero_cliente')
                ->distinct()
                ->chunk(200, function ($clientes) {

                    foreach ($clientes as $cliente) {
                        ProcessCustomerDataJob::dispatch($cliente->numero_cliente)
                            ->onQueue('cliente');
                    }
                });
        } catch (\Throwable $e) {
            Log::error("❌ Cliente nao encontrado: {$e->getMessage()}");
        }



        Log::info("✅ Dispatch concluído.");
    }
}
