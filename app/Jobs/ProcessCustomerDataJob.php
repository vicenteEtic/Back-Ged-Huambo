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

class ProcessCustomerDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $timeout = 600;
    public $tries = 10;

    protected string $numeroCliente;

    public function __construct(string $numeroCliente)
    {
        $this->numeroCliente = $numeroCliente;
    }

    public function handle()
    {
        Log::info("🚀 Cliente: {$this->numeroCliente}");

        $entity = Entities::where('customer_number', $this->numeroCliente)->first();

        if (!$entity) {
            Log::warning("⚠️ Cliente não encontrado: {$this->numeroCliente}");
            return;
        }

        /**
         * 🔥 Buscar apólices com chunkById (evita OFFSET lento)
         */
        DB::table('policies_staging')
            ->where('numero_cliente', $this->numeroCliente)
            ->chunkById(500, function ($policies) use ($entity) {

                $policyNumbers = collect($policies)
                    ->pluck('numero_apolice')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($policyNumbers)) return;

                /**
                 * 🚀 Dispatch apenas com IDs
                 */
                ProcessCustomerPoliciesJob::dispatch(
                    $entity->id,
                    $policyNumbers
                )->onQueue('cliente');
            });
    }
}