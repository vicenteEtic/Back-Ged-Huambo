<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CustomerKYTService
{

    public $timeout = 100; // 15 minutos (aumentado)
    public $tries = 8;     // Tenta 3 vezes antes de desistir
    public $backoff = 10;  // Espera 10 segundos entre tentativas
    public function RiskAssessmentEntity(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        // 🔍 1. Verifica primeiro no cache
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // 🔥 2. Só consulta a BD se não existir
        $risk = RiskAssessment::where('entity_id', $customer->id)
            ->latest()
            ->first();

        // ❌ Sem avaliação
        if (!$risk) {
            Log::warning("⚠️ Avaliação de risco ausente para cliente {$customer->customer_number}");

            $data = [
                'risk_id' => null,
                'alert_priority' => false,
                'valid' => false
            ];

            Cache::put($cacheKey, $data, now()->addHours(20));

            return $data;
        }

        $isHighRisk = in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]);

        if ($isHighRisk) {
            Log::warning(
                "⚠️ Cliente {$customer->customer_number} com avaliação de risco {$risk->diligence} (ID: {$risk->id})"
            );
        }

        $data = [
            'risk_id' => $risk->id,
            'alert_priority' => $isHighRisk,
            'valid' => !$isHighRisk
        ];

        // 💾 3. Guarda no cache
        Cache::put($cacheKey, $data, now()->addHours(20));

        return $data;
    }

    public function runAllChecksMemory(Entities $customer, array $policies): void
    {
        $policies = $this->normalizePolicies($policies);

        Log::info("🔍 KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkHighCapitalIncrease($customer, $policies);
        $this->checkEarlyRedemption($customer, $policies);
        $this->checkHighPremium($customer, $policies);
        $this->checkMultipleShortPolicies($customer, $policies);

        $this->checkPolicyChurning($customer, $policies);
        $this->checkRapidReplacement($customer, $policies);

        Log::info("✅ KYT FINISHED", ['customer' => $customer->customer_number]);
    }

    /* =========================
       NORMALIZAÇÃO
    ========================== */

    private function normalizePolicies(array $policies): array
    {
        return array_map(function ($p) {
            return [
                'numero_apolice' => $p['Numero_Apolice'] ?? $p['numero_apolice'] ?? null,
                'numero_cliente' => $p['Numero_Cliente'] ?? $p['numero_cliente'] ?? null,
                'descricao_produto' => strtoupper(trim($p['Descricao_Produto'] ?? $p['descricao_produto'] ?? '')),
                'estado_apolice' => $this->normalizeStatus($p['Estado_Apolice'] ?? $p['estado_apolice'] ?? null),
                'data_inicio' => $this->parseDate($p['Data_Inicio'] ?? $p['data_inicio'] ?? null),
                'data_fim' => $this->parseDate($p['Data_Fim'] ?? $p['data_fim'] ?? null),
                'capital' => $this->toFloat($p['Capital'] ?? $p['capital'] ?? 0),
                'premium_total' => $this->toFloat($p['Premio_Total'] ?? $p['premium_total'] ?? 0),
                'interest' => $this->toFloat($p['Juros'] ?? $p['interest'] ?? 0),
            ];
        }, $policies);
    }

    private function toFloat($value): float
    {
        return is_numeric($value) ? (float)$value : 0.0;
    }

    private function normalizeStatus(?string $status): string
    {
        $status = strtoupper(trim($status ?? ''));
        return match ($status) {
            'NORMAL', 'ATIVA' => 'active',
            'CANCELADA', 'C/ CARTA' => 'cancelled',
            'ANULADA', 'TERMINADA', 'INACTIVOS', 'Anulada' => 'terminated',
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
        } catch (\Exception $e) {
            return null;
        }
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
    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, '.', ' ');
    }

    /* =========================
       REGRAS KYT COM NÚMEROS DAS APÓLICES
    ========================== */

    private function checkHighCapitalIncrease(Entities $customer, array $policies): void
    {
        // 🔹 Agrupa por produto
        $policiesByProduct = [];

        foreach ($policies as $p) {
            $produto = $p['descricao_produto'] ?? 'OUTROS';
            $policiesByProduct[$produto][] = $p;
        }

        foreach ($policiesByProduct as $produto => $group) {

            // 🔹 filtra válidas
            $valid = array_filter(
                $group,
                fn($p) =>
                $p['data_inicio'] && $p['capital'] > 1000000
            );

            if (count($valid) < 2) continue;

            // 🔹 ordena por data (mais recentes primeiro)
            usort(
                $valid,
                fn($a, $b) =>
                strtotime($b['data_inicio']) - strtotime($a['data_inicio'])
            );

            // 🔥 pega apenas as últimas 20 para análise
            $valid = array_slice($valid, 0, 20);

            // 🔹 reordena cronologicamente para análise correta
            usort(
                $valid,
                fn($a, $b) =>
                strtotime($a['data_inicio']) - strtotime($b['data_inicio'])
            );

            $first = $valid[0];

            for ($i = 1; $i < count($valid); $i++) {
                $current = $valid[$i];

                $days = $this->safeDays($first['data_inicio'], $current['data_inicio']);
                if ($days === null || $days == 0 || $days > 60) continue;

                if ($first['premium_total'] <= 0 && $current['premium_total'] <= 0) continue;

                $increaseRate = ($current['capital'] - $first['capital']) / $first['capital'];
                if ($increaseRate < 0.40) continue;

                // 🔥 apenas apólices analisadas
                $apolices = [
                    $first['numero_apolice'],
                    $current['numero_apolice']
                ];

                // 🔹 período analisado
                $startDate = $first['data_inicio'];
                $endDate   = $current['data_inicio'];

                $description = sprintf(
                    "Produto: %s | Apólices analisadas: %s | Período: %s → %s | Capital: %s → %s | Aumento: %.0f%% em %d dias",
                    $produto,
                    implode(' → ', $apolices),
                    $startDate,
                    $endDate,
                    $this->formatMoney($first['capital']),
                    $this->formatMoney($current['capital']),
                    $increaseRate * 100,
                    $days
                );

                $this->createAlert(
                    $customer,
                    "Aumento elevado de capital na apólice",
                    $description,
                    'Alto',
                    30
                );

                // 🔁 baseline evolutivo
                $first = $current;
            }
        }
    }

    private function checkEarlyRedemption(Entities $customer, array $policies): void
    {
        // 🔹 Agrupa por produto
        $grouped = [];

        foreach ($policies as $p) {
            $produto = $p['descricao_produto'] ?? 'OUTROS';
            $grouped[$produto][] = $p;
        }

        foreach ($grouped as $produto => $group) {

            // 🔹 filtra apenas apólices com cancelamento/resgate < 12 meses
            $valid = array_filter($group, function ($p) {
                $days = $this->safeDays($p['data_inicio'], $p['data_fim']);

                return $days !== null
                    && $days < 365
                    && in_array($p['estado_apolice'], ['cancelled', 'terminated']);
            });

            if (count($valid) < 1) continue;

            // 🔹 ordena por data mais recente
            usort(
                $valid,
                fn($a, $b) =>
                strtotime($b['data_inicio'] ?? '1970') - strtotime($a['data_inicio'] ?? '1970')
            );

            // 🔥 últimas 20 analisadas
            $latest = array_slice($valid, 0, 20);

            // 🔹 extrai apólices analisadas
            $apolices = array_column($latest, 'numero_apolice');

            // 🔹 período analisado
            $firstDate = $latest[0]['data_inicio'] ?? null;
            $lastDate  = end($latest)['data_fim'] ?? null;

            // 🔹 métricas adicionais (nível AML)
            $totalCapital = array_sum(array_column($latest, 'capital'));
            $totalPremium = array_sum(array_column($latest, 'premium_total'));

            $description = sprintf(
                "Produto: %s | Apólices analisadas: %s | Período: %s → %s | Total Capital: %s | Total Prêmio: %s | Resgates antes de data prevista",
                $produto,
                implode(', ', $apolices),
                $firstDate,
                $lastDate,
                $this->formatMoney($totalCapital),
                $this->formatMoney($totalPremium)
            );

            $this->createAlert(
                $customer,
                'Resgate antecipado de apólice',
                $description,
                'Alto',
                20
            );
        }
    }

    private function checkHighPremium(Entities $customer, array $policies): void
    {
        // 🔹 Agrupa por produto
        $grouped = [];

        foreach ($policies as $p) {
            $produto = $p['descricao_produto'] ?? 'OUTROS';
            $grouped[$produto][] = $p;
        }

        foreach ($grouped as $produto => $group) {

            // 🔹 filtra válidas
            $valid = array_filter($group, function ($p) {
                return $p['capital'] > 0 && $p['premium_total'] > 0;
            });

            if (count($valid) < 1) continue;

            // 🔹 ordena por data (mais recentes primeiro)
            usort(
                $valid,
                fn($a, $b) =>
                strtotime($b['data_inicio'] ?? '1970') - strtotime($a['data_inicio'] ?? '1970')
            );

            // 🔥 últimas 20 analisadas
            $latest = array_slice($valid, 0, 20);

            // 🔹 cálculo com TODAS (regra KYT correta)
            $totalCapital = array_sum(array_column($valid, 'capital'));
            $totalPremium = array_sum(array_column($valid, 'premium_total'));

            if ($totalCapital <= 0 || $totalPremium <= 0) continue;

            $ratio = $totalPremium / $totalCapital;

            if ($ratio >= 0.08) {

                // 🔹 apenas apólices analisadas (últimas 20)
                $apolices = array_column($latest, 'numero_apolice');

                // 🔹 período analisado (melhora auditoria)
                $firstDate = $latest[0]['data_inicio'] ?? null;
                $lastDate  = end($latest)['data_inicio'] ?? null;

                $description = sprintf(
                    "Produto: %s | Últimas 20 apólices: %s | Período: %s → %s | Capital total: %s | Prêmio total: %s | Ratio: %.2f%%",
                    $produto,
                    implode(', ', $apolices),
                    $firstDate,
                    $lastDate,
                    $this->formatMoney($totalCapital),
                    $this->formatMoney($totalPremium),
                    $ratio * 100
                );

                $this->createAlert(
                    $customer,
                    "Prémio elevado com risco baixo",
                    $description,
                    'Alto',
                    25
                );
            }
        }
    }

    private function checkMultipleShortPolicies(Entities $customer, array $policies): void
    {
        // 🔹 filtrar apólices curtas válidas
        $short = array_filter($policies, function ($p) {
            $days = $this->safeDays($p['data_inicio'], $p['data_fim']);

            return $days !== null
                && $days >= 90 && $days <= 180 // 3-6 meses
                && $p['capital'] > 0
                && $p['data_inicio']
                && $p['data_fim'];
        });

        if (count($short) < 3) return;

        // 🔹 ordenar por data
        usort(
            $short,
            fn($a, $b) =>
            strtotime($a['data_inicio']) - strtotime($b['data_inicio'])
        );

        $window = [];
        $alerts = [];

        for ($i = 0; $i < count($short); $i++) {
            $window = [$short[$i]];
            $totalCapital = $short[$i]['capital'];

            for ($j = $i + 1; $j < count($short); $j++) {
                $gap = $this->safeDays(
                    $short[$i]['data_inicio'],
                    $short[$j]['data_inicio']
                );

                // 🔹 janela de 60 dias (fragmentação)
                if ($gap !== null && $gap <= 60) {
                    $window[] = $short[$j];
                    $totalCapital += $short[$j]['capital'];
                } else {
                    break;
                }
            }

            // 🔥 regra principal AML
            if (count($window) >= 3 && $totalCapital >= 300000) {

                $apolices = array_column($window, 'numero_apolice');

                $description = sprintf(
                    "Cliente: %s | Apólices curtas (3-6 meses): %s | Quantidade: %d | Capital total: %s | Período: %s → %s",
                    $customer->customer_number,
                    implode(', ', $apolices),
                    count($window),
                    $this->formatMoney($totalCapital),
                    $window[0]['data_inicio'],
                    end($window)['data_inicio']
                );

                $this->createAlert(
                    $customer,
                    'Substituição ou cancelamento repetido',
                    $description,
                    'Médio',
                    15
                );

                return; // evita múltiplos alerts duplicados
            }
        }
    }


    private function checkPolicyChurning(Entities $customer, array $policies): void
    {
        // 🔹 filtrar cancelamentos válidos (últimos 12 meses)
        $terminated = array_filter($policies, function ($p) {
            if (!in_array($p['estado_apolice'], ['cancelled', 'terminated'])) {
                return false;
            }

            if (!$p['data_fim']) return false;

            return Carbon::parse($p['data_fim'])->gte(now()->subYear());
        });

        if (count($terminated) < 3) return;

        // 🔹 ordenar por data de cancelamento
        usort(
            $terminated,
            fn($a, $b) =>
            strtotime($a['data_fim']) - strtotime($b['data_fim'])
        );

        $clusters = 0;

        // 🔹 detectar frequência (ex: cancelamentos próximos)
        for ($i = 1; $i < count($terminated); $i++) {
            $gap = $this->safeDays(
                $terminated[$i - 1]['data_fim'],
                $terminated[$i]['data_fim']
            );

            if ($gap !== null && $gap <= 60) {
                $clusters++;
            }
        }

        // 🔥 regra AML: frequência relevante
        if ($clusters < 2) return;

        // 🔹 limitar a 20 para auditoria
        $latest = array_slice($terminated, -20);

        $apolices = array_column($latest, 'numero_apolice');

        $description = sprintf(
            "Cliente: %s | Cancelamentos frequentes detectados: %s | Total: %d | Clusters (<=60 dias): %d",
            $customer->customer_number,
            implode(', ', $apolices),
            count($terminated),
            $clusters
        );

        $this->createAlert(
            $customer,
            'Churn de apólices (trocas frequentes)',
            $description,
            'Médio',
            20
        );
    }

    private function checkRapidReplacement(Entities $customer, array $policies): void
    {
        // 🔹 ordenar por data
        usort(
            $policies,
            fn($a, $b) =>
            strtotime($a['data_inicio'] ?? '1970') - strtotime($b['data_inicio'] ?? '1970')
        );

        $chains = [];
        $currentChain = [];

        for ($i = 1; $i < count($policies); $i++) {
            $prev = $policies[$i - 1];
            $curr = $policies[$i];

            if (!in_array($prev['estado_apolice'] ?? null, ['terminated', 'cancelled'])) {
                continue;
            }

            $gap = $this->safeDays($prev['data_fim'], $curr['data_inicio']);

            if ($gap !== null && $gap <= 7) {

                if (empty($currentChain)) {
                    $currentChain[] = $prev;
                }

                $currentChain[] = $curr;
            } else {
                if (count($currentChain) >= 3) {
                    $chains[] = $currentChain;
                }
                $currentChain = [];
            }
        }

        // 🔹 última cadeia
        if (count($currentChain) >= 3) {
            $chains[] = $currentChain;
        }

        if (empty($chains)) return;

        // 🔹 usar a maior cadeia
        usort($chains, fn($a, $b) => count($b) - count($a));
        $chain = $chains[0];

        // 🔹 filtrar últimos 12 meses
        $chain = array_filter($chain, function ($p) {
            return isset($p['data_inicio']) &&
                Carbon::parse($p['data_inicio'])->gte(now()->subYear());
        });

        if (count($chain) < 3) return;

        // 🔹 limitar a 20
        $chain = array_slice($chain, -20);

        $apolices = [];
        for ($i = 1; $i < count($chain); $i++) {
            $apolices[] = $chain[$i - 1]['numero_apolice'] . ' → ' . $chain[$i]['numero_apolice'];
        }

        $description = sprintf(
            "Cliente: %s | Substituições rápidas (<=7 dias): %s | Cadeia: %d eventos",
            $customer->customer_number,
            implode(', ', $apolices),
            count($chain)
        );

        $this->createAlert(
            $customer,
            'Substituição rápida de apólice',
            $description,
            'Alto',
            15 // ✅ corrigido
        );
    }


    private function checkThirdPartyPayments(Entities $customer, array $policies): void
    {
        foreach ($policies as $policy) {

            // 🔹 Ignora apólices sem prémio ou capital
            if (!$policy['premium_total'] || !$policy['capital']) continue;

            // 🔹 Simulação de pagador; assumimos que $policy['payer'] existe:
            // ['name' => string, 'relation' => string|null, 'origin' => string|null]
            $payer = $policy['payer'] ?? null;

            if (!$payer) continue; // sem informação do pagador, ignora

            // 🔹 Pagador não é o próprio segurado
            $isThirdParty = ($payer['relation'] ?? 'self') !== 'self';

            // 🔹 Montante relevante (exemplo > 100.000 Kz)
            $isHighAmount = $policy['premium_total'] >= 100000;

            // 🔹 Somente apólices iniciais ou renovações recentes (últimos 12 meses)
            $isRecentPolicy = $policy['data_inicio'] && Carbon::parse($policy['data_inicio'])->gte(now()->subYear());

            if ($isThirdParty && $isHighAmount && $isRecentPolicy) {

                $description = sprintf(
                    "Apólice: %s | Prémio: %s | Pagador: %s (%s) | Origem fundos: %s | Segurado: %s",
                    $policy['numero_apolice'],
                    $this->formatMoney($policy['premium_total']),
                    $payer['name'] ?? 'Desconhecido',
                    $payer['relation'] ?? 'Desconhecida',
                    $payer['origin'] ?? 'Desconhecida',
                    $customer->social_denomination
                );

                $this->createAlert(
                    $customer,
                    'Pagamentos de prémios por terceiros',
                    $description,
                    'Alto',         // nível de risco
                    25

                );
            }
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
        $riskData = $this->RiskAssessmentEntity($customer);
        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => $description,
            ],
            [
                'alert_priority' => $riskData['alert_priority'],
                'risk_assessment_id' => $riskData['risk_id'],
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
