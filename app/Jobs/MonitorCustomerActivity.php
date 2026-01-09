<?php

namespace App\Jobs;

use App\Models\Entities\Entities;
use App\Models\Transation\Policies;
use App\Models\Alert\Alert;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class MonitorCustomerActivity implements ShouldQueue
{
    use Dispatchable, Queueable;

    public function handle(): void
    {
        Log::info('🔍 KYT Monitor iniciado');

        $customers = Entities::all();

        foreach ($customers as $customer) {
            $this->checkHighCapitalIncrease($customer);
            $this->checkEarlyRedemption($customer);
            $this->checkHighPremiumLowRisk($customer);
            $this->checkMultipleShortPolicies($customer);
            $this->checkPolicyChurning($customer);
            $this->checkRapidPolicyReplacement($customer);
        }

        Log::info('✅ KYT Monitor finalizado');
    }

    private function checkHighCapitalIncrease(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->orderBy('start_date', 'desc')
            ->take(2)
            ->get();

        if ($policies->count() < 2) return;

        $current = $policies[0];
        $previous = $policies[1];

        if ($current->capital > ($previous->capital * 10)) {
            $description = sprintf(
                "Aumento abrupto de capital detectado.\nApólice anterior: %s (Capital: %s)\nApólice atual: %s (Capital: %s)",
                $previous->contract_number,
                number_format($previous->capital, 2, ',', '.'),
                $current->contract_number,
                number_format($current->capital, 2, ',', '.')
            );

            $this->createAlertOnce($customer->id, 'Aumento significativo de capital', $description, 'Médio', 'checkHighCapitalIncrease','30');
        }
    }

    private function checkEarlyRedemption(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->where('status', 'terminated')
            ->get();

        foreach ($policies as $policy) {
            if (!$policy->start_date || !$policy->end_date) continue;

            $days = Carbon::parse($policy->start_date)
                ->diffInDays(Carbon::parse($policy->end_date));

            if ($days < 365) {
                $description = sprintf(
                    "Apólice resgatada antes de 12 meses.\nApólice: %s\nPeríodo: %s a %s (%d dias)\nCapital: %s",
                    $policy->contract_number,
                    $policy->start_date,
                    $policy->end_date,
                    $days,
                    number_format($policy->capital, 2, ',', '.')
                );

                $this->createAlertOnce($customer->id, 'Resgate antecipado de capital', $description, 'Médio', 'checkEarlyRedemption','30');
            }
        }
    }

    private function checkHighPremiumLowRisk(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->where('status', 'active')
            ->get();

        foreach ($policies as $policy) {
            $capital = (float) $policy->capital;
            $premium_total = (float) $policy->premium_total;

            if ($capital <= 10000 && $premium_total >= 5000) {
                $description = sprintf(
                    "Prémio elevado incompatível com risco segurado.\nApólice: %s\nCapital: %s\nPrémio total: %s",
                    $policy->contract_number,
                    number_format($capital, 2, ',', '.'),
                    number_format($premium_total, 2, ',', '.')
                );

                $this->createAlertOnce($customer->id, 'Prémio elevado com baixo risco', $description, 'Médio', 'checkHighPremiumLowRisk','30');
            }
        }
    }

    private function checkMultipleShortPolicies(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->where('status', 'active')
            ->get()
            ->filter(function ($policy) {
                if (!$policy->start_date || !$policy->end_date) return false;
                $days = Carbon::parse($policy->start_date)
                    ->diffInDays(Carbon::parse($policy->end_date));
                return $days < 180;
            });

        if ($policies->count() >= 3) {
            $list = $policies->pluck('contract_number')->join(', ');
            $description = sprintf(
                "Múltiplas apólices de curta duração detectadas.\nApólices: %s\nTotal: %d",
                $list,
                $policies->count()
            );

            $this->createAlertOnce($customer->id, 'Fragmentação de contratos em apólices curtas', $description, 'Baixo', 'checkMultipleShortPolicies','19');
        }
    }

    private function checkPolicyChurning(Entities $customer): void
    {
        $terminated = Policies::where('entity_id', $customer->id)
            ->where('status', 'terminated')
            ->where('end_date', '>=', now()->subYear())
            ->get();

        if ($terminated->count() >= 3) {
            $list = $terminated->pluck('contract_number')->join(', ');
            $description = sprintf(
                "Cancelamentos frequentes de apólices.\nApólices canceladas: %s\nTotal: %d\nCliente: %s",
                $list,
                $terminated->count(),
                $customer->social_denomination
            );

            $this->createAlertOnce($customer->id, 'Substituição frequente de apólices', $description, 'Médio', 'checkPolicyChurning','30');
        }
    }

    private function checkRapidPolicyReplacement(Entities $customer): void
    {
        $policies = Policies::where('entity_id', $customer->id)
            ->orderBy('start_date')
            ->get();

        if ($policies->count() < 2) return;

        for ($i = 1; $i < $policies->count(); $i++) {
            $gap = Carbon::parse($policies[$i]->start_date)
                ->diffInDays(Carbon::parse($policies[$i - 1]->end_date));

            if ($gap <= 7) {
                $description = sprintf(
                    "Substituição rápida de apólice.\nApólice anterior: %s\nApólice atual: %s\nIntervalo: %d dias",
                    $policies[$i - 1]->contract_number,
                    $policies[$i]->contract_number,
                    $gap
                );

                $this->createAlertOnce($customer->id, 'Substituição frequente e rápida de contratos', $description, 'Médio', 'checkRapidPolicyReplacement','30');
                break;
            }
        }
    }

    private function createAlertOnce(
        int $entityId,
        string $type,
        string $description,
        string $severity,
        string $list,
        string $score
    ): void {
        if (Alert::where('entity_id', $entityId)
            ->where('type', $type)
            ->where('description', $description)
            ->exists()
        ) {
            return;
        }

        $entitie = Entities::find($entityId);
        $alert = Alert::create([
            'entity_id'   => $entityId,
            'type'        => $type,
            'category'    => "KYT",
            'description' => $description,
            'level'       => $severity,
            'list'        => $list,
            'score'       => $score,
            'name'        => $entitie->social_denomination ?? "UNKNOWN",
            'created_at'  => now(),
            'updated_at'  => now(),
        ]);

        $host = config('app.url');
        SendGrupoAlertEmailJob::dispatch($alert->id, $host)->onQueue('high');
        Log::warning("🚨 {$type} | Cliente {$entityId}");
    }
}
