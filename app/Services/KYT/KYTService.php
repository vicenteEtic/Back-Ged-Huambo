<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use App\Enum\TypeEntity;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class CustomerKYTService
{
    public function RiskAssessmentEntity(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $risk = RiskAssessment::where('entity_id', $customer->id)
            ->latest()
            ->first();

        if (!$risk) {
            Log::warning("Avaliação de risco ausente para cliente {$customer->customer_number}");

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
                "Cliente {$customer->customer_number} com avaliação de risco {$risk->diligence} (ID: {$risk->id})"
            );
        }

        $data = [
            'risk_id' => $risk->id,
            'alert_priority' => $isHighRisk,
            'valid' => !$isHighRisk
        ];

        Cache::put($cacheKey, $data, now()->addHours(20));

        return $data;
    }

    public function runAllChecksMemory(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = []
    ): void {
        $policies = $this->normalizePolicies($policies);

        Log::info("KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkAbruptCapitalIncrease($customer, $policies, $changes);
        $this->checkPolicyLifecycleAbuse($customer, $policies, $changes, $refunds);

        Log::info("KYT FINISHED", ['customer' => $customer->customer_number]);
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
       REGRA KYT - AUMENTO ABRUPTO DE CAPITAL
    ========================== */

    private function checkAbruptCapitalIncrease(Entities $customer, array $policies, array $changes = []): void
    {
        $isCollective = (int)($customer->entity_type ?? 0) === TypeEntity::COLECTIVA->value;

        $relevantProducts = $isCollective
            ? [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'VIDA RISCO GRUPO',
                'GRUPO-CAPITAL P/ADERENTE',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ]
            : [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
                'SEGURO BAI VIDA',
                'SEGURO VIDA-FIXE',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'SEGURO VIDA CRÉDITO',
                'SEGURO VIDA CRÉDITO (AKZ)',
                'SEG. VIDA CRÉDITO PENSIONISTA',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];

        $threshold30 = $isCollective ? 0.60 : 0.40;
        $threshold90 = $isCollective ? 1.00 : 0.70;

        $filtered = array_values(array_filter($policies, function ($p) use ($relevantProducts) {
            return in_array(strtoupper(trim($p['descricao_produto'] ?? '')), $relevantProducts)
                && ($p['capital'] ?? 0) > 0;
        }));

        if (count($filtered) < 2) return;

        usort($filtered, fn($a, $b) =>
            strtotime($a['data_inicio'] ?? '1970') <=> strtotime($b['data_inicio'] ?? '1970')
        );

        $justifiedMotives = ['herança', 'mudança de emprego', 'promoção', 'evento económico'];

        foreach ($changes as $change) {
            $motivo = strtolower(trim($change->motivo_alteracao ?? ''));
            if (in_array($motivo, $justifiedMotives)) {
                Log::info("KYT aumento justificado para {$customer->customer_number}: {$motivo}");
                return;
            }
        }

        $first = $filtered[0];
        $firstCapital = (float)($first['capital'] ?? 0);
        $firstDate = $this->safeDate($first['data_inicio']);

        if ($firstCapital <= 0 || !$firstDate) return;

        $createdAlerts = [];

        for ($i = 1; $i < count($filtered); $i++) {
            $curr = $filtered[$i];
            $currCapital = (float)($curr['capital'] ?? 0);
            $currDate = $this->safeDate($curr['data_inicio']);

            if ($currCapital <= 0 || !$currDate) continue;

            $increaseRate = ($currCapital - $firstCapital) / $firstCapital;
            if ($increaseRate <= 0) continue;

            $daysDiff = $firstDate->diffInDays($currDate);

            $isTrigger = ($daysDiff <= 30 && $increaseRate >= $threshold30)
                      || ($daysDiff <= 90 && $increaseRate >= $threshold90);

            if (!$isTrigger) continue;

            $key = $curr['numero_apolice'] ?? "policy_{$i}";
            if (in_array($key, $createdAlerts)) continue;
            $createdAlerts[] = $key;

            $score = 20;
            if ($increaseRate >= 1.0) $score += 10;
            if ($daysDiff <= 30) $score += 5;

            $entityLabel = $isCollective ? 'Coletiva' : 'Singular';
            $limiarDesc = $daysDiff <= 30
                ? "≥" . ($isCollective ? '60' : '40') . "% em 30 dias"
                : "≥" . ($isCollective ? '100' : '70') . "% em 90 dias";

            $description = sprintf(
                "AUMENTO ABRUPTO DE CAPITAL ENTRE APÓLICES\n" .
                "Cliente: %s | Tipo: %s\n\n" .
                "Apólice de referência (1.ª):\n" .
                "  N.º: %s | Produto: %s\n" .
                "  Capital: %s | Início: %s\n\n" .
                "Nova apólice:\n" .
                "  N.º: %s | Produto: %s\n" .
                "  Capital: %s | Início: %s\n\n" .
                "Variação: +%.2f%% em %d dias\n" .
                "Limiar aplicado: %s\n" .
                "Justificação: %s\n\n" .
                "Interpretação AML:\n" .
                "Aumento significativo de capital sem justificação económica\n" .
                "compatível com perfil de risco elevado (layering/estruturação).",
                $customer->customer_number,
                $entityLabel,
                $first['numero_apolice'],
                $first['descricao_produto'],
                $this->formatMoney($firstCapital),
                $first['data_inicio'],
                $curr['numero_apolice'],
                $curr['descricao_produto'],
                $this->formatMoney($currCapital),
                $curr['data_inicio'],
                $increaseRate * 100,
                $daysDiff,
                $limiarDesc,
                'Nenhuma'
            );
            $this->createAlert(
                $customer,
                'Aumento abrupto de capital entre apólices',
                $description,
                'Alto',
                $score
            );
        }
    }

    /* =========================
       REGRA KYT - ABUSO DO CICLO DE VIDA DAS APÓLICES (2,5,6)
    ========================== */

    private function checkPolicyLifecycleAbuse(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = []
    ): void {
        $isCollective = (int)($customer->entity_type ?? 0) === TypeEntity::COLECTIVA->value;

        $relevantProducts = $isCollective
            ? [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'GRUPO-CAPITAL P/ADERENTE',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ]
            : [
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
                'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
                'SEGURO BAI VIDA',
                'SEGURO VIDA-FIXE',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];

        $minEvents = $isCollective ? 3 : 2;
        $maxDays = $isCollective ? 90 : 60;
        $minPremium = $isCollective ? 10000000.00 : 1000000.00;

        $filtered = array_values(array_filter($policies, function ($p) use ($relevantProducts) {
            return in_array(strtoupper(trim($p['descricao_produto'] ?? '')), $relevantProducts)
                && ($p['premium_total'] ?? 0) > 0;
        }));

        if (count($filtered) < $minEvents) return;

        usort($filtered, fn($a, $b) =>
            strtotime($a['data_inicio'] ?? '1970') <=> strtotime($b['data_inicio'] ?? '1970')
        );

        $events = [];
        foreach ($filtered as $p) {
            $inicio = $this->safeDate($p['data_inicio'] ?? null);
            $fim = $this->safeDate($p['data_fim'] ?? null);
            if (!$inicio || !$fim) continue;

            $estado = $p['estado_apolice'] ?? '';
            $isCancelled = in_array($estado, ['cancelled', 'terminated']);

            $temResgate = false;
            foreach ($refunds as $r) {
                if (($r['Numero_Apolice'] ?? null) === $p['numero_apolice']) {
                    $temResgate = true;
                    break;
                }
            }

            if (!$isCancelled && !$temResgate) continue;

            $events[] = $p;
        }

        if (count($events) < $minEvents) return;

        $windowStart = $this->safeDate($events[0]['data_inicio']);
        $windowEnd = $this->safeDate($events[count($events) - 1]['data_inicio']);
        if (!$windowStart || !$windowEnd) return;

        $windowDays = $windowStart->diffInDays($windowEnd);
        if ($windowDays > $maxDays) return;

        $totalPremium = array_sum(array_column($events, 'premium_total'));
        if ($totalPremium < $minPremium) return;

        $apolices = array_column($events, 'numero_apolice');
        $entityLabel = $isCollective ? 'Coletiva' : 'Singular';

        $description = sprintf(
            "ABUSO DO CICLO DE VIDA DAS APÓLICES\n" .
            "Cliente: %s | Tipo: %s\n\n" .
            "Eventos detetados: %d\n" .
            "Janela temporal: %d dias (limiar: %d dias)\n" .
            "Prémio total: %s (limiar: %s)\n" .
            "Apólices: %s\n\n" .
            "Interpretação AML:\n" .
            "Cancelamentos, resgates ou substituições reiterados em curto período,\n" .
            "compatível com fragmentação de valores e reciclagem financeira.",
            $customer->customer_number,
            $entityLabel,
            count($events),
            $windowDays,
            $maxDays,
            $this->formatMoney($totalPremium),
            $this->formatMoney($minPremium),
            implode(', ', $apolices)
        );

        $score = 15;
        if ($totalPremium >= $minPremium * 2) $score += 5;
        if (count($events) >= $minEvents + 1) $score += 5;
        if ($windowDays <= $maxDays / 2) $score += 5;

        $this->createAlert(
            $customer,
            'Abuso do ciclo de vida das apólices',
            $description,
            'Alto',
            $score
        );
    }

    /**
     * 2º Cenário: Subscrição de múltiplas apólices de curta duração para fragmentar valores elevados.
     *
     * Particulares: ≥2 eventos (60 dias); ≥ AOA 1.000.000,00
     * Coletivas: ≥3 eventos (90 dias); ≥ AOA 10.000.000,00
     * Múltiplos resgates ou cancelamentos com substituição reiterados.
     *
     * Produtos:
     * - Seguro de Poupança Vida (SPV) INDIVIDUAL
     * - Seguro de Poupança Vida (SPV) INDIVIDUAL TEMPORARIO
     * - Seguro BAI Vida
     * - Seguro Vida-Fixe
     * - PRÉMIO FIXO
     * - PRÉMIO VARIÁVEL
     * - FUNDO DE PENSÕES ABERTO - NOSSA Reforma
     */
    public static function scenarioPolicyFragmenting(Entities $customer): array
    {
        $isCollective = (int)($customer->entity_type ?? 0) === \App\Enum\TypeEntity::COLECTIVA->value;

        if ($isCollective) {
            $produtos = [
                'SEGURO DE POUPANÇA VIDA (SPV) GRUPO FECHADO',
                'GRUPO-CAPITAL P/ADERENTE',
                'GRUPO-CAPITAL PESSOAS DIVERSAS',
                'PRÉMIO FIXO',
                'PRÉMIO VARIÁVEL',
                'FUNDO DE PENSÕES BAI',
                'FUNDO DE PENSÕES ABERTO - NOSSA REFORMA',
            ];
            $baseDate = now()->subDays(5);
            $policies = [];
            $changes = [];
            $refunds = [];
            $i = 1;
            foreach ($produtos as $produto) {
                $polNum = "POL-FRAG-COL-{$i}";
                $start = (clone $baseDate)->addHours($i * 8);
                $end = (clone $start)->addDays(60);
                $policies[] = [
                    'numero_apolice' => $polNum,
                    'premium_total' => 1500000.00,
                    'capital' => 30000000.00,
                    'data_inicio' => $start->format('Y-m-d H:i:s'),
                    'data_fim' => $end->format('Y-m-d'),
                    'estado_apolice' => 'Anulada',
                    'descricao_produto' => $produto,
                ];
                $changes[] = [
                    'numero_apolice' => $polNum,
                    'tipo_alteracao' => 'CANCELAMENTO COM SUBSTITUICAO',
                    'valor_anterior' => 30000000.00,
                    'novo_valor' => 0.00,
                    'motivo_alteracao' => 'Substituição de apólice por novo contrato',
                ];
                $refunds[] = [
                    'Numero_Apolice' => $polNum,
                    'Valor_Estorno' => 1400000.00,
                    'Data_Estorno' => $end->format('Y-m-d'),
                    'Nome_Beneficiario' => $customer->social_denomination,
                ];
                $i++;
            }
            return [
                'policies' => $policies,
                'changes' => $changes,
                'refunds' => $refunds,
                'receipts' => [],
            ];
        }

        $produtos = [
            'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL',
            'SEGURO DE POUPANÇA VIDA (SPV) INDIVIDUAL TEMPORARIO',
            'SEGURO BAI VIDA',
        ];

        $baseDate = now()->subDays(3);
        $policies = [];
        $changes = [];
        $refunds = [];

        $i = 1;
        foreach ($produtos as $produto) {
            $polNum = "POL-FRAG-PART-{$i}";
            $start = (clone $baseDate)->addHours($i * 6);
            $end = (clone $start)->addDays(45);
            $policies[] = [
                'numero_apolice' => $polNum,
                'premium_total' => 350000.00,
                'capital' => 7000000.00,
                'data_inicio' => $start->format('Y-m-d H:i:s'),
                'data_fim' => $end->format('Y-m-d'),
                'estado_apolice' => 'Anulada',
                'descricao_produto' => $produto,
            ];
            $changes[] = [
                'numero_apolice' => $polNum,
                'tipo_alteracao' => 'RESGATE ANTECIPADO',
                'valor_anterior' => 7000000.00,
                'novo_valor' => 0.00,
                'motivo_alteracao' => 'Resgate total antes da maturidade',
            ];
            $refunds[] = [
                'Numero_Apolice' => $polNum,
                'Valor_Estorno' => 330000.00,
                'Data_Estorno' => $end->format('Y-m-d'),
                'Nome_Beneficiario' => $customer->social_denomination,
            ];
            $i++;
        }

        return [
            'policies' => $policies,
            'changes' => $changes,
            'refunds' => $refunds,
            'receipts' => [],
        ];
    }

    /* =========================
        UTILITÁRIOS
    ========================== */

    private function safeDate($date)
    {
        try {
            if (!$date) return null;
            if ($date === '0000-00-00' || $date === '1900-01-01') return null;

            return Carbon::parse($date);
        } catch (\Exception $e) {
            return null;
        }
    }

    private function getPolicyAmount(array $policy): float
    {
        return (float) (
            $policy['premium_total']
            ?? $policy['Premio_Total']
            ?? $policy['premio_simples']
            ?? 0
        );
    }

    private function money(float $value): string
    {
        return number_format($value, 2, ',', ' ') . ' Kz';
    }

    private function formatPolicyContext(array $policy): string
    {
        $produto = $policy['descricao_produto'] ?? 'N/A';
        $apolice = $policy['numero_apolice'] ?? 'N/A';

        $premium = $this->money($this->getPolicyAmount($policy));
        $capital = $this->money((float) ($policy['capital'] ?? 0));

        $inicio = $policy['data_inicio'] ?? 'N/A';
        $fim = $policy['data_fim'] ?? 'N/A';

        return "
Produto: {$produto}
Prémio pago: {$premium}
Capital segurado: {$capital}
Data início: {$inicio}
Data fim: {$fim}
";
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
            Log::warning("ALERTA {$type}", [
                'cliente' => $customer->customer_number,
                'descricao' => $description
            ]);
        }
    }
}
