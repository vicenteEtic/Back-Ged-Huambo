<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class CustomerKYTService
{
    public $timeout = 100; // minutos
    public $tries = 5;     
    public $backoff = 10;  

    /**
     * Executa todas as verificações KYT para um cliente
     */
    public function runAllChecksMemory(Entities $customer, array $policies): void
    {
        // Normaliza apólices e remove duplicadas
        $policies = $this->normalizePolicies($policies);
        $policies = $this->uniquePolicies($policies);

        Log::info("🔍 KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        if (empty($policies)) return;

        // Executa todas as regras
        $alerts = [];
        $alerts = array_merge($alerts, $this->checkHighCapitalIncrease($customer, $policies));
        $alerts = array_merge($alerts, $this->checkEarlyRedemption($customer, $policies));
        $alerts = array_merge($alerts, $this->checkHighPremium($customer, $policies));
        $alerts = array_merge($alerts, $this->checkMultipleShortPolicies($customer, $policies));
        $alerts = array_merge($alerts, $this->checkPolicyChurning($customer, $policies));
        $alerts = array_merge($alerts, $this->checkRapidReplacement($customer, $policies));

        // Cria alertas no final, só após análise completa
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

    /**
     * Normaliza todas as apólices
     */
    private function normalizePolicies(array $policies): array
    {
        return array_map(function ($p) {
            return [
                'numero_apolice'    => $p['Numero_Apolice'] ?? $p['numero_apolice'] ?? null,
                'numero_cliente'    => $p['Numero_Cliente'] ?? $p['numero_cliente'] ?? null,
                'descricao_produto' => strtoupper(trim($p['Descricao_Produto'] ?? $p['descricao_produto'] ?? '')),
                'estado_apolice'    => $this->normalizeStatus($p['Estado_Apolice'] ?? $p['estado_apolice'] ?? null),
                'data_inicio'       => $this->parseDate($p['Data_Inicio'] ?? $p['data_inicio'] ?? null),
                'data_fim'          => $this->parseDate($p['Data_Fim'] ?? $p['data_fim'] ?? null),
                'capital'           => $this->toFloat($p['Capital'] ?? $p['capital'] ?? 0),
                'premium_total'     => $this->toFloat($p['Premio_Total'] ?? $p['premium_total'] ?? 0),
                'interest'          => $this->toFloat($p['Juros'] ?? $p['interest'] ?? 0),
            ];
        }, $policies);
    }

    /**
     * Remove apólices duplicadas
     */
    private function uniquePolicies(array $policies): array
    {
        $unique = [];
        foreach ($policies as $p) {
            $key = $p['numero_apolice'] . '_' . $p['data_inicio'] . '_' . $p['capital'];
            $unique[$key] = $p;
        }
        return array_values($unique);
    }

    private function toFloat($value): float
    {
        if (is_string($value)) $value = str_replace(',', '.', trim($value));
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim($status ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS','Anulada' => 'terminated',
            default => 'unknown'
        };
    }

    private function parseDate(?string $date): ?string
    {
        if (!$date) return null;
        $invalid = ['ANULADA', 'TERMINADA', 'INACTIVOS', 'NORMAL', ''];
        if (in_array(strtoupper(trim($date)), $invalid)) return null;
        try {
            $dt = preg_replace('/\.\d+$/', '', $date);
            return Carbon::parse($dt)->format('Y-m-d H:i:s');
        } catch (\Exception) {
            return null;
        }
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
            if ($days === null || $days == 0 || $days > 60) continue;
            if ($previous['premium_total'] <= 0 && $current['premium_total'] <= 0) continue;

            $increaseRate = ($current['capital'] - $previous['capital']) / $previous['capital'];
            if ($increaseRate < 0.40) continue;

            $alerts[] = [
                'type' => 'Aumento elevado de capital',
                'description' => sprintf(
                    "Cliente: %s | Apólices: %s → %s | Capital: %.2f → %.2f | Prêmios: %.2f → %.2f | Aumento: %.0f%% em %d dias",
                    $current['numero_cliente'],
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
            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);
            if ($days !== null && $days < 365 && in_array($p['estado_apolice'], ['cancelled', 'terminated'])) {
                $alerts[] = [
                    'type' => 'Resgate antecipado',
                    'description' => sprintf(
                        "Cliente: %s | Apólice: %s | Cancelada após %d dias | Capital: %.2f | Prêmio: %.2f",
                        $p['numero_cliente'],
                        $p['numero_apolice'],
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
            $uniqueApolices = array_unique(array_column($policies, 'numero_apolice'));
            $alerts[] = [
                'type' => 'Prêmio elevado',
                'description' => sprintf(
                    "Cliente: %s | Apólices: %s | Capital total: %.2f | Prêmio total: %.2f | Ratio: %.2f%%",
                    $customer->customer_number,
                    implode(', ', $uniqueApolices),
                    $totalCapital,
                    $totalPremium,
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
        $alerts = [];
        $short = array_filter($policies, function ($p) {
            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);
            if ($days === null || $days < 90 || $days > 180) return false;
            if ($p['capital'] < 1000000) return false;
            if (!$p['data_fim']) return false;
            return Carbon::parse($p['data_fim'])->gte(now()->subYears(2));
        });

        if (count($short) < 3) return [];

        usort($short, fn($a, $b) => strtotime($a['data_inicio']) - strtotime($b['data_inicio']));

        $cluster = 0;
        for ($i = 1; $i < count($short); $i++) {
            $gap = $this->safeDays($short[$i - 1]['data_fim'], $short[$i]['data_inicio']);
            if ($gap !== null && $gap <= 30) $cluster++;
        }

        if ($cluster >= 2) {
            $uniqueApolices = array_unique(array_column($short, 'numero_apolice'));
            $alerts[] = [
                'type' => 'Padrão suspeito de apólices curtas',
                'description' => sprintf(
                    "Cliente: %s | Apólices curtas detectadas: %s | Quantidade: %d",
                    $customer->customer_number,
                    implode(', ', $uniqueApolices),
                    count($uniqueApolices)
                ),
                'severity' => 'Médio',
                'score' => 20
            ];
        }
        return $alerts;
    }

    private function checkPolicyChurning(Entities $customer, array $policies): array
    {
        $alerts = [];
        $terminated = array_filter($policies, fn($p) => in_array($p['estado_apolice'], ['cancelled', 'terminated']));
        if (count($terminated) >= 3) {
            $uniqueApolices = array_unique(array_column($terminated, 'numero_apolice'));
            $alerts[] = [
                'type' => 'Churn de apólices',
                'description' => sprintf(
                    "Cliente: %s | Apólices canceladas: %s | Total: %d",
                    $customer->customer_number,
                    implode(', ', $uniqueApolices),
                    count($uniqueApolices)
                ),
                'severity' => 'Médio',
                'score' => 20
            ];
        }
        return $alerts;
    }

    private function checkRapidReplacement(Entities $customer, array $policies): array
    {
        $alerts = [];
        usort($policies, fn($a, $b) => strtotime($a['data_inicio'] ?? '1970') - strtotime($b['data_inicio'] ?? '1970'));
        for ($i = 1; $i < count($policies); $i++) {
            $prev = $policies[$i - 1];
            $curr = $policies[$i];
            if ($prev['estado_apolice'] !== 'terminated') continue;

            $gap = $this->safeDays($prev['data_fim'], $curr['data_inicio']);
            if ($gap !== null && $gap <= 7) {
                $alerts[] = [
                    'type' => 'Substituição rápida',
                    'description' => sprintf(
                        "Cliente: %s | Substituição rápida | Apólice anterior: %s | Nova apólice: %s | Gap: %d dias",
                        $customer->customer_number,
                        $prev['numero_apolice'],
                        $curr['numero_apolice'],
                        $gap
                    ),
                    'severity' => 'Médio',
                    'score' => 15
                ];
                break;
            }
        }
        return $alerts;
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