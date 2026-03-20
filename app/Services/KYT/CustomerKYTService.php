<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerKYTService
{
    public function runAllChecksMemory(Entities $customer, array $policies): void
    {
        $policies = $this->normalizePolicies($policies);

        Log::info("🔍 KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        $this->checkHighCapitalIncrease($customer, $policies);
        $this->checkEarlyRedemption($customer, $policies);
        $this->checkHighPremium($customer, $policies);
        $this->checkMultipleShortPolicies($customer, $policies);
        $this->checkPolicyChurning($customer, $policies);
        $this->checkRapidReplacement($customer, $policies);
    }

    /* =========================
       NORMALIZAÇÃO
    ========================== */

    private function normalizePolicies(array $policies): array
    {
        return array_map(function ($p) {
            return [
                'numero_apolice' => $p['numero_apolice'] ?? $p['Numero_Apolice'] ?? null,
                'numero_cliente' => $p['numero_cliente'] ?? $p['Numero_Cliente'] ?? null,

                'descricao_produto' => strtoupper(trim($p['descricao_produto'] ?? $p['Descricao_Produto'] ?? '')),

                'estado_apolice' => $this->normalizeStatus($p['estado_apolice'] ?? $p['Estado_Apolice'] ?? null),

                'data_inicio' => $p['data_inicio'] ?? $p['Data_Inicio'] ?? null,
                'data_fim' => $p['data_fim'] ?? $p['Data_Fim'] ?? null,

                'capital' => (float)($p['capital'] ?? $p['Capital'] ?? 0),
                'premium_total' => (float)($p['premium_total'] ?? $p['Premio_Total'] ?? 0),

                'interest' => (float)($p['interest'] ?? $p['Juros'] ?? 0),
            ];
        }, $policies);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim($status ?? ''));

        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'C/ CARTA', 'CANCELADA' => 'cancelled',
            'ANULADA', 'TERMINADA' => 'terminated',
            default => 'unknown'
        };
    }

    private function safeDays(?string $start, ?string $end): ?int
    {
        try {
            if (!$start || !$end) return null;

            return Carbon::parse($start)->diffInDays(Carbon::parse($end));
        } catch (\Exception $e) {
            return null;
        }
    }

    /* =========================
       REGRAS KYT
    ========================== */

    private function checkHighCapitalIncrease(Entities $customer, array $policies): void
    {
        $valid = array_filter($policies, fn($p) => !empty($p['data_inicio']));
        usort($valid, fn($a,$b) => strtotime($b['data_inicio']) - strtotime($a['data_inicio']));

        if (count($valid) < 2) return;

        $current  = $valid[0];
        $previous = $valid[1];

        if ($previous['capital'] <= 0) return;

        $days = $this->safeDays($previous['data_inicio'], $current['data_inicio']);
        if ($days === null || $days > 60) return;

        $increaseRate = ($current['capital'] - $previous['capital']) / $previous['capital'];

        if ($increaseRate < 0.40) return;

        $premiumRate = ($previous['premium_total'] > 0)
            ? ($current['premium_total'] - $previous['premium_total']) / $previous['premium_total']
            : 0;

        $description = sprintf(
            "Apolice %s: capital aumentou de %.2f para %.2f (%.0f%%) em %d dias. Premio variou %.0f%%. Juros: %.2f",
            $current['numero_apolice'],
            $previous['capital'],
            $current['capital'],
            $increaseRate * 100,
            $days,
            $premiumRate * 100,
            $current['interest']
        );

        $this->createAlert($customer, 'Aumento elevado de capital na apólice', $description, 'Alto', 30);
    }

    private function checkEarlyRedemption(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {

            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);
            if ($days === null || $days >= 365) continue;

            if (!in_array($p['estado_apolice'], ['cancelled','terminated'])) continue;

            $description = sprintf(
                "Apolice %s cancelada apos %d dias (inicio: %s, fim: %s)",
                $p['numero_apolice'],
                $days,
                $p['data_inicio'],
                $p['data_fim']
            );

            $this->createAlert($customer, 'Resgate antecipado de apólice', $description, 'Alto', 20);
        }
    }

    private function checkHighPremium(Entities $customer, array $policies): void
    {
        foreach ($policies as $p) {

            if ($p['capital'] <= 0 || $p['premium_total'] <= 0) continue;

            $ratio = $p['premium_total'] / $p['capital'];

            if ($ratio < 0.08) continue;

            $description = sprintf(
                "Apolice %s com premio %.2f e capital %.2f (ratio: %.2f%%)",
                $p['numero_apolice'],
                $p['premium_total'],
                $p['capital'],
                $ratio * 100
            );

            $this->createAlert($customer, 'Prémio elevado com risco baixo', $description, 'Alto', 25);
        }
    }

    private function checkMultipleShortPolicies(Entities $customer, array $policies): void
    {
        $short = array_filter($policies, function($p) {
            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);
            return $days !== null && $days >= 90 && $days <= 180;
        });

        if (count($short) < 2) return;

        $description = sprintf(
            "%d apolices com duracao entre 90 e 180 dias detectadas",
            count($short)
        );

        $this->createAlert($customer, 'Múltiplas apólices de curta duração', $description, 'Médio', 15);
    }

    private function checkPolicyChurning(Entities $customer, array $policies): void
    {
        $terminated = array_filter($policies, fn($p) =>
            in_array($p['estado_apolice'], ['cancelled','terminated'])
        );

        if (count($terminated) < 2) return;

        $description = sprintf(
            "%d apolices canceladas/terminadas detectadas para o cliente",
            count($terminated)
        );

        $this->createAlert($customer, 'Churn de apólices (trocas frequentes)', $description, 'Médio', 20);
    }

    private function checkRapidReplacement(Entities $customer, array $policies): void
    {
        usort($policies, fn($a,$b) => strtotime($a['data_inicio']) - strtotime($b['data_inicio']));

        for ($i = 1; $i < count($policies); $i++) {

            $prev = $policies[$i-1];
            $curr = $policies[$i];

            if ($prev['estado_apolice'] !== 'terminated') continue;

            $gap = $this->safeDays($prev['data_fim'], $curr['data_inicio']);

            if ($gap === null || $gap > 7) continue;

            $description = sprintf(
                "Substituicao rapida: apolice %s encerrada e nova iniciada em %d dias",
                $prev['numero_apolice'],
                $gap
            );

            $this->createAlert($customer, 'Substituição rápida de apólice', $description, 'Médio', 15);
            break;
        }
    }

    /* =========================
       ALERTAS
    ========================== */

    private function createAlert(
        Entities $customer,
        string $type,
        string $description,
        string $severity,
        int $score
    ): void {


        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => $description,
            ],
            [
                'category' => 'KYT',
                'level' => $severity,
                'name' => $customer->social_denomination,
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated || $alert->wasChanged()) {

            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))->onQueue('high');

            Log::warning("🚨 ALERTA {$type}", [
                'cliente' => $customer->customer_number,
                'descricao' => $description
            ]);
        }
    }
}