<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerKYTService
{
    public $timeout = 100;
    public $tries = 5;
    public $backoff = 10;

    public function runAllChecksMemory(Entities $customer, array $policies): void
    {
        $policies = $this->normalizePolicies($policies);
        $policies = $this->uniquePolicies($policies);

        Log::info("🔍 KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        if (empty($policies)) return;

        $alerts = [];
        $alerts = array_merge($alerts, $this->checkHighCapitalIncrease($customer, $policies));
        $alerts = array_merge($alerts, $this->checkEarlyRedemption($customer, $policies));
        $alerts = array_merge($alerts, $this->checkHighPremium($customer, $policies));
        $alerts = array_merge($alerts, $this->checkMultipleShortPolicies($customer, $policies));
        $alerts = array_merge($alerts, $this->checkPolicyChurning($customer, $policies));
        $alerts = array_merge($alerts, $this->checkRapidReplacement($customer, $policies));

        foreach ($alerts as $alertData) {
            $this->createAlert(
                $customer,
                $alertData['type'],
                $alertData['description'],
                $alertData['severity'],
                $alertData['score']
            );
        }

        Log::info("✅ KYT FINISHED", ['customer' => $customer->customer_number]);
    }

    /* =========================
       NORMALIZAÇÃO
    ========================== */

    private function normalizePolicies(array $policies): array
    {
        return array_map(function ($p) {
            return [
                'numero_apolice'    => $p['numero_apolice'] ?? null,
                'numero_cliente'    => $p['numero_cliente'] ?? null,
                'descricao_produto' => strtoupper(trim($p['descricao_produto'] ?? '')),
                'estado_apolice'    => $this->normalizeStatus($p['estado_apolice'] ?? null),
                'data_inicio'       => $this->parseDate($p['data_inicio'] ?? null),
                'data_fim'          => $this->parseDate($p['data_fim'] ?? null),
                'capital'           => $this->toFloat($p['capital'] ?? 0),
                'premium_total'     => $this->toFloat($p['premium_total'] ?? 0),
                'interest'          => $this->toFloat($p['interest'] ?? 0),
            ];
        }, $policies);
    }

    private function uniquePolicies(array $policies): array
    {
        $unique = [];
        foreach ($policies as $p) {
            $key = $p['numero_apolice'] . '_' . $p['data_inicio'] . '_' . $p['capital'];
            $unique[$key] = $p;
        }
        return array_values($unique);
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim($status ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS' => 'terminated',
            default => 'unknown'
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        try {
            return Carbon::parse($date)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
    }

    private function toFloat($value): float
    {
        if (is_string($value)) $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }

    /* =========================
       HELPERS
    ========================== */

    private function getEffectiveEndDate(array $p): ?string
    {
        return in_array($p['estado_apolice'], ['terminated', 'cancelled'])
            ? now()->format('Y-m-d H:i:s')
            : $p['data_fim'];
    }

    private function safeDays(?string $start, ?string $end): ?int
    {
        if (!$start || !$end) return null;
        try {
            return Carbon::parse($start)->diffInDays(Carbon::parse($end));
        } catch (\Exception) {
            return null;
        }
    }

    private function limitApolices(array $policies, int $limit = 10): array
    {
        return array_slice($policies, -$limit);
    }

    /* =========================
       REGRAS KYT
    ========================== */

    private function checkHighCapitalIncrease(Entities $customer, array $policies): array
    {
        $alerts = [];
        $valid = array_filter($policies, fn($p) => $p['data_inicio'] && $p['capital'] > 1000000);
        usort($valid, fn($a, $b) => strtotime($b['data_inicio']) - strtotime($a['data_inicio']));

        for ($i = 1; $i < count($valid); $i++) {
            $current  = $valid[$i - 1];
            $previous = $valid[$i];
            $days = $this->safeDays($previous['data_inicio'], $current['data_inicio']);
            if ($days === null || $days > 60) continue;

            $increaseRate = ($current['capital'] - $previous['capital']) / max($previous['capital'], 1);
            if ($increaseRate < 0.40) continue;

            $alerts[] = [
                'type' => 'Aumento elevado de capital',
                'description' => sprintf(
                    "Cliente: %s\nApólices: %s → %s\nCapital: %.2f → %.2f\nPrêmio: %.2f → %.2f\nAumento: %.0f%% em %d dias",
                    $customer->customer_number,
                    $previous['numero_apolice'],
                    $current['numero_apolice'],
                    $previous['capital'],
                    $current['capital'],
                    $previous['premium_total'],
                    $current['premium_total'],
                    $increaseRate * 100,
                    $days
                ),
                'severity' => 'Alto',
                'score' => 30
            ];
        }

        return $alerts;
    }

    private function checkEarlyRedemption(Entities $customer, array $policies): array
    {
        $alerts = [];
        foreach ($policies as $p) {
            $end = $this->getEffectiveEndDate($p);
            $days = $this->safeDays($p['data_inicio'], $end);
            if ($days !== null && $days < 365 && in_array($p['estado_apolice'], ['cancelled', 'terminated'])) {
                $alerts[] = [
                    'type' => 'Resgate antecipado',
                    'description' => sprintf(
                        "Cliente: %s\nApólice: %s\nData início: %s | Data fim: %s\nDias ativos: %d | Capital: %.2f | Prêmio: %.2f",
                        $p['numero_cliente'],
                        $p['numero_apolice'],
                        $p['data_inicio'] ?? 'N/A',
                        $end ?? 'N/A',
                        $days,
                        $p['capital'],
                        $p['premium_total']
                    ),
                    'severity' => 'Alto',
                    'score' => 20
                ];
            }
        }
        return $alerts;
    }

    private function checkHighPremium(Entities $customer, array $policies): array
    {
        $alerts = [];
        $totalCapital = array_sum(array_column($policies, 'capital'));
        $totalPremium = array_sum(array_column($policies, 'premium_total'));
        if ($totalCapital <= 0 || $totalPremium <= 0) return [];

        $ratio = $totalPremium / $totalCapital;
        if ($ratio >= 0.08) {
            $last10 = $this->limitApolices($policies);
            $details = array_map(function ($p) {
                return sprintf("%s | Capital: %.2f | Prêmio: %.2f", $p['numero_apolice'], $p['capital'], $p['premium_total']);
            }, $last10);

            $alerts[] = [
                'type' => 'Prêmio elevado',
                'description' => sprintf(
                    "Cliente: %s\nÚltimas 10 apólices:\n%s\nTotal de apólices: %d | Ratio: %.2f%%",
                    $customer->customer_number,
                    implode("\n", $details),
                    count($policies),
                    $ratio * 100
                ),
                'severity' => 'Alto',
                'score' => 25
            ];
        }

        return $alerts;
    }

    private function checkMultipleShortPolicies(Entities $customer, array $policies): array
    {
        $short = array_filter($policies, function ($p) {
            $end = $this->getEffectiveEndDate($p);
            $days = $this->safeDays($p['data_inicio'], $end);
            return $days !== null && $days >= 90 && $days <= 180 && $p['capital'] >= 1000000;
        });

        if (count($short) < 3) return [];

        $last10 = $this->limitApolices($short);
        $details = array_map(function ($p) {
            return sprintf("%s | Início: %s | Fim: %s | Capital: %.2f | Prêmio: %.2f",
                $p['numero_apolice'],
                $p['data_inicio'] ?? 'N/A',
                $this->getEffectiveEndDate($p) ?? 'N/A',
                $p['capital'],
                $p['premium_total']
            );
        }, $last10);

        return [[
            'type' => 'Apólices curtas suspeitas',
            'description' => sprintf(
                "Cliente: %s\nÚltimas 10 apólices curtas detectadas:\n%s\nTotal de curtas: %d",
                $customer->customer_number,
                implode("\n", $details),
                count($short)
            ),
            'severity' => 'Médio',
            'score' => 20
        ]];
    }

    private function checkPolicyChurning(Entities $customer, array $policies): array
    {
        $terminated = array_filter($policies, fn($p) => in_array($p['estado_apolice'], ['cancelled', 'terminated']));
        if (count($terminated) < 3) return [];

        $last10 = $this->limitApolices($terminated);
        $details = array_map(function ($p) {
            return sprintf("%s | Capital: %.2f | Prêmio: %.2f",
                $p['numero_apolice'],
                $p['capital'],
                $p['premium_total']
            );
        }, $last10);

        return [[
            'type' => 'Churn de apólices',
            'description' => sprintf(
                "Cliente: %s\nÚltimas 10 apólices canceladas/terminadas:\n%s\nTotal: %d",
                $customer->customer_number,
                implode("\n", $details),
                count($terminated)
            ),
            'severity' => 'Médio',
            'score' => 20
        ]];
    }

    private function checkRapidReplacement(Entities $customer, array $policies): array
    {
        usort($policies, fn($a, $b) => strtotime($a['data_inicio']) - strtotime($b['data_inicio']));
        for ($i = 1; $i < count($policies); $i++) {
            $prev = $policies[$i - 1];
            $curr = $policies[$i];
            if (!in_array($prev['estado_apolice'], ['terminated', 'cancelled'])) continue;

            $gap = $this->safeDays($this->getEffectiveEndDate($prev), $curr['data_inicio']);
            if ($gap !== null && $gap <= 7) {
                return [[
                    'type' => 'Substituição rápida',
                    'description' => sprintf(
                        "Cliente: %s\nApólice anterior: %s | Capital: %.2f | Prêmio: %.2f\nNova apólice: %s | Capital: %.2f | Prêmio: %.2f\nGap: %d dias",
                        $customer->customer_number,
                        $prev['numero_apolice'],
                        $prev['capital'],
                        $prev['premium_total'],
                        $curr['numero_apolice'],
                        $curr['capital'],
                        $curr['premium_total'],
                        $gap
                    ),
                    'severity' => 'Médio',
                    'score' => 15
                ]];
            }
        }
        return [];
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
        $hash = md5($type . $description);

        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'description' => $description,
            ],
            [
                'type' => $type,
                'description' => $description,
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