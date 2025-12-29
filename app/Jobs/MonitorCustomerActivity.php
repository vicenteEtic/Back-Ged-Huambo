<?php

namespace App\Jobs;

use App\Models\Entities\Entities; // Model correto para os clientes
use App\Models\Transation\Policies;
use App\Models\Transation\Transaction as TransationTransaction;
use App\Models\Alert\Alert as ModelsAlert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;

class MonitorCustomerActivity implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle()
    {
        Log::info('Iniciando MonitorCustomerActivity Job');

        $customers = Entities::all(); // pega todos os clientes
        Log::info('Total de clientes encontrados: ' . $customers->count());

        foreach ($customers as $customer) {
            Log::info("Processando cliente ID: {$customer->id}");
            $this->checkPEP($customer);
            $this->checkSmallTransactions($customer);
            $this->checkPolicyChanges($customer);
           // $this->checkEarlyRedemption($customer);
        }

        Log::info('Finalizado MonitorCustomerActivity Job');
    }

    private function checkSmallTransactions(Entities $customer)
    {
        Log::info("Verificando transações pequenas para cliente ID: {$customer->id}");

        $transactions = TransationTransaction::where('entity_id', $customer->id)
            ->where('amount', '<', 1000)
            ->where('transaction_date', '>=', now()->subMonth())
            ->get();

        Log::info("Transações pequenas encontradas: " . $transactions->count());

        if ($transactions->count() >= 5) {
            ModelsAlert::create([
                'entity_id' => $customer->id,
                'type' => 'Multiple Small Transactions',
                'description' => 'Cliente fez muitas transações pequenas em um curto período.',
            ]);
            Log::info("Alerta 'Multiple Small Transactions' criado para cliente ID: {$customer->id}");
        }
    }

    private function checkPolicyChanges(Entities $customer)
    {
        Log::info("Verificando mudanças de capital para cliente ID: {$customer->id}");

        $policies = Policies::where('entity_id', $customer->id)
            ->orderBy('start_date', 'desc')
            ->take(2)
            ->get();

        Log::info("Políticas encontradas: " . $policies->count());

        if ($policies->count() == 2) {
            $prev = $policies[1];
            $curr = $policies[0];

            if ($curr->capital > $prev->capital * 10) {
                ModelsAlert::create([
                    'entity_id' => $customer->id,
                    'type' => 'High Capital Increase',
                    'description' => "Cliente aumentou capital de {$prev->capital} para {$curr->capital}.",
                ]);
                Log::info("Alerta 'High Capital Increase' criado para cliente ID: {$customer->id}");
            }
        }
    }

   private function checkEarlyRedemption(Entities $customer)
{
    Log::info("Verificando resgate antecipado para cliente ID: {$customer->id}");

    $policies = Policies::where('entity_id', $customer->id)
        ->where('status', 'terminated')
        ->where('end_date', '<', now())
        ->get();

    Log::info("Políticas terminadas encontradas: " . $policies->count());

    foreach ($policies as $policy) {
        try {
            if (empty($policy->start_date) || empty($policy->end_date)) {
                Log::warning("Datas inválidas para policy ID: {$policy->id}");
                continue;
            }

            $startDate = \Carbon\Carbon::parse($policy->start_date);
            $endDate = \Carbon\Carbon::parse($policy->end_date);

            $diff = $endDate->diffInDays($startDate);

            if ($diff < 365) {
                ModelsAlert::create([
                    'entity_id' => $customer->id,
                    'type' => 'Early Redemption',
                    'description' => "Política resgatada antes de completar 1 ano.",
                ]);

                Log::info(
                    "Alerta 'Early Redemption' criado para cliente ID: {$customer->id}, policy ID: {$policy->id}"
                );
            }
        } catch (\Exception $e) {
            Log::error("Erro ao processar policy ID: {$policy->id}", [
                'message' => $e->getMessage(),
                'start_date' => $policy->start_date,
                'end_date' => $policy->end_date
            ]);
        }
    }
}


    private function checkPEP(Entities $customer)
    {
        Log::info("Verificando PEP para cliente ID: {$customer->id}");
        // Lógica para verificar PEP (Politicamente Exposto)
    }
}
