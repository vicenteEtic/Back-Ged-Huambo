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

        /**
         * 🔥 Buscar apólices com chunkById (evita OFFSET)
         */
        DB::table('policies_staging')
            ->where('numero_cliente', $numeroCliente)
            ->chunkById(500, function ($policies) use ($entity) {

                $policyNumbers = $policies->pluck('numero_apolice')
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                if (empty($policyNumbers)) return;

                /**
                 * 🔥 Pré-busca dados relacionados (evita 5 queries no job seguinte)
                 */
                $changes = DB::table('policy_changes_staging')
                    ->whereIn('numero_apolice', $policyNumbers)->get()->toArray();
                $refunds = DB::table('apol_anulada_estorno')
                    ->whereIn('n_apolice', $policyNumbers)->get()->toArray();
                $receipts = DB::table('recibos_cobrados')
                    ->whereIn('numero_apolice', $policyNumbers)->get()->toArray();
                $beneficiaries = DB::table('beneficiarios_staging')
                    ->whereIn('numero_apolice', $policyNumbers)->get()->toArray();

                /**
                 * 🚀 Dispatch com dados pré-buscados
                 */
                ProcessCustomerPoliciesJob::dispatch(
                    $entity->id,
                    $policyNumbers,
                    $policies->toArray(),
                    $changes,
                    $refunds,
                    $receipts,
                    $beneficiaries
                )->onQueue('cliente');
            }, 'id');
    }
}