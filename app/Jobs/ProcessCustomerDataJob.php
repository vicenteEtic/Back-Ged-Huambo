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

        $entity = Entities::where('customer_number', $numeroCliente)->first();

        if (!$entity) {
            Log::warning("⚠️ Cliente não encontrado: {$numeroCliente}");
            return;
        }

        $allPolicyNumbers = [];

        DB::table('policies_staging')
            ->where('numero_cliente', $numeroCliente)
            ->chunkById(500, function ($policies) use (&$allPolicyNumbers) {

                $policyNumbers = $policies->pluck('numero_apolice')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $allPolicyNumbers = array_merge($allPolicyNumbers, $policyNumbers);
            }, 'id');

        $allPolicyNumbers = array_unique($allPolicyNumbers);

        if (!empty($allPolicyNumbers)) {
            ProcessCustomerPoliciesJob::dispatch(
                $entity->id,
                $allPolicyNumbers
            )->onQueue('cliente');

            Log::info("✅ Policies enfileiradas para cliente {$numeroCliente}", [
                'total' => count($allPolicyNumbers),
            ]);
        } else {
            Log::warning("⚠️ Nenhuma policy encontrada para cliente {$numeroCliente}");
        }
    }
}