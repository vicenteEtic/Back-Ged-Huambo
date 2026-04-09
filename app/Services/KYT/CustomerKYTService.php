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



    
    public function runAllChecksMemory(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [] // 👈 ADICIONAR
    ): void
    {
        $policies = $this->normalizePolicies($policies);

        Log::info("🔍 KYT START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies)
        ]);

        if (empty($policies)) return;

     //   $this->checkHighCapitalIncrease($customer, $changes);
      // 🔥 Agora passa os dados de estorno reais para a detecção de Early Redemption
    //$this->checkEarlyRedemption($customer, $policies, $refunds);
     
    //$this->checkHighPremium($customer, $policies);
     $this->checkMultipleShortPolicies($customer, $policies);

    //  $this->checkPolicyChurning($customer, $policies);
     $this->checkRapidReplacement($customer, $policies);

        Log::info("✅ KYT FINISHED ", ['customer' => $customer->customer_number]);
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


    private function checkHighCapitalIncrease(Entities $customer, array $changes): void
{
    foreach ($changes as $change) {

        $tipo = strtoupper(trim($change->tipo_alteracao ?? ''));

        // 🔥 Só capital (ajusta conforme teus dados)
        if (!str_contains($tipo, 'ALTER')) continue;

        $old = (float) $change->valor_anterior;
        $new = (float) $change->novo_valor;

        if ($old <= 0) continue;

        $increaseRate = ($new - $old) / $old;

        // 🔥 REGRA AML
        if ($increaseRate < 0.30) continue;

        $motivo = strtolower(trim($change->motivo_alteracao ?? ''));

        $motivoValido = in_array($motivo, [
            'herança',
            'mudança de emprego',
            'promoção'
        ]);

        $score = 10;

        if ($increaseRate >= 0.50) $score += 10;
        if (!$motivoValido) $score += 10;

        // 🔥 SE FOR > 80% → ALERTA FORTE
        if ($increaseRate >= 0.80) $score += 10;

        $description = sprintf(
            "Apólice: %s | Tipo: %s | Capital: %s → %s | Aumento: %.2f%% | Motivo: %s",
            $change->numero_apolice,
            $tipo,
            $this->formatMoney($old),
            $this->formatMoney($new),
            $increaseRate * 100,
            $change->motivo_alteracao ?? 'Não informado'
        );

        $this->createAlert(
            $customer,
            "Aumento elevado de capital na apólice",
            $description,
            'Alto',
            $score
        );
    }
}
   
private function checkEarlyRedemption(Entities $customer, array $policies, array $refunds = []): void
{
    foreach ($policies as $p) {

        // 🔒 Apenas apólices canceladas
        if (!in_array($p['estado_apolice'], ['cancelled', 'terminated'])) {
            continue;
        }

        // 🔒 Datas
        $dataInicio = $this->parseDate($p['data_inicio']);
        $dataCancelamentoRaw = $p['data_anulacao'] ?? $p['data_fim'];

        if ($dataCancelamentoRaw === '1900-01-01 00:00:00') {
            $dataCancelamentoRaw = null;
        }

        $dataCancelamento = $this->parseDate($dataCancelamentoRaw);

        if (!$dataInicio || !$dataCancelamento) continue;

        try {
            $inicio = Carbon::parse($dataInicio);
            $fim = Carbon::parse($dataCancelamento);
        } catch (\Exception $e) {
            continue;
        }

        if ($fim->lt($inicio)) continue;

        $dias = $inicio->diffInDays($fim);

        // 🔥 REGRA PRINCIPAL (menos de 12 meses)
        if ($dias >= 365 || $dias <= 0) continue;

        // 🔥 Valor pago REAL
        $valorPago = (float)($p['premium_total'] > 0 
            ? $p['premium_total'] 
            : ($p['premio_simples'] ?? 0)
        );

        if ($valorPago <= 0) continue;

        // 🚫 Não temos estorno real → assumir 0 ou integrar depois
        $valorRecebido = 0;

        $perda = $valorPago - $valorRecebido;

        if ($perda <= 0) continue;

        // 🔥 Percentagem de perda (CRÍTICO AML)
        $percentualPerda = $perda / $valorPago;

        // 🔥 FILTRO AML (10% - 20%)
        if ($percentualPerda < 0.10) continue;

        // 🔥 FILTRO PRODUTO (opcional mas recomendado)
        $produto = strtoupper($p['descricao_produto'] ?? '');
        $isProdutoSensivel = str_contains($produto, 'VIDA') || str_contains($produto, 'POUP');

        // Score dinâmico
        $score = 20;

        if ($percentualPerda >= 0.20) $score += 5;
        if ($dias < 180) $score += 5;
        if ($isProdutoSensivel) $score += 5;

        $description = sprintf(
            "KYT EARLY REDEMPTION\n" .
            "Produto: %s | Apólice: %s\n" .
            "Duração: %d dias (<365)\n" .
            "Financeiro: Pago [%s] | Recebido [%s] | Perda [%s] (%.2f%%)\n" .
            "Motivo: %s",
            $produto,
            $p['numero_apolice'],
            $dias,
            $this->formatMoney($valorPago),
            $this->formatMoney($valorRecebido),
            $this->formatMoney($perda),
            $percentualPerda * 100,
            $p['motivo_anulacao'] ?? 'N/A'
        );

        $this->createAlert(
            $customer,
            'Resgate Antecipado de apólice',
            $description,
            'Alto',
            $score
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
    // 🔹 filtrar apólices curtas válidas (3-6 meses)
    $short = array_filter($policies, function ($p) {
        $days = $this->safeDays($p['data_inicio'], $p['data_fim']);

        return $days !== null
            && $days >= 90 && $days <= 180
            && $p['premium_total'] > 0
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

    for ($i = 0; $i < count($short); $i++) {

        $window = [$short[$i]];
        $totalPremium = $short[$i]['premium_total'];
        $cancelledEarlyCount = 0;

        // 🔍 verificar se foi cancelada cedo (<180 dias)
        $daysFirst = $this->safeDays($short[$i]['data_inicio'], $short[$i]['data_fim']);
        if ($daysFirst !== null && $daysFirst < 180) {
            $cancelledEarlyCount++;
        }

        for ($j = $i + 1; $j < count($short); $j++) {

            $gap = $this->safeDays(
                $short[$i]['data_inicio'],
                $short[$j]['data_inicio']
            );

            // 🔹 janela de fragmentação (até 60 dias)
            if ($gap !== null && $gap <= 60) {

                $window[] = $short[$j];
                $totalPremium += $short[$j]['premium_total'];

                $days = $this->safeDays($short[$j]['data_inicio'], $short[$j]['data_fim']);

                if ($days !== null && $days < 180) {
                    $cancelledEarlyCount++;
                }

            } else {
                break;
            }
        }

        // 🔥 REGRA AML PRINCIPAL
        if (count($window) >= 3 && $totalPremium >= 300000) {

            $apolices = array_column($window, 'numero_apolice');

            // 🔥 SCORE DINÂMICO
            $score = 15;

            if ($totalPremium >= 500000) $score += 5;
            if (count($window) >= 5) $score += 5;
            if ($cancelledEarlyCount >= 2) $score += 5; // comportamento suspeito real

            $description = sprintf(
                "KYT MULTIPLE SHORT POLICIES (Fragmentação)\n" .
                "Cliente: %s\n" .
                "Apólices: %s\n" .
                "Qtd: %d | Prémio Total: %s\n" .
                "Cancelamentos precoces: %d\n" .
                "Período: %s → %s\n" .
                "Indicador AML: Possível smurfing/layering (fragmentação de valores)",
                $customer->customer_number,
                implode(', ', $apolices),
                count($window),
                $this->formatMoney($totalPremium),
                $cancelledEarlyCount,
                $window[0]['data_inicio'],
                end($window)['data_inicio']
            );

            $this->createAlert(
                $customer,
                '"Churn de apólices (trocas frequentes)',
                $description,
                'Médio',
                $score
            );

            return; // evita duplicados
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
            'Trocas Frequentes de Apólices',
            $description,
            'Médio',
            20
        );
    }

   private function checkRapidReplacement(Entities $customer, array $policies): void
{
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

        // 🔒 apenas apólices canceladas
        if (!in_array($prev['estado_apolice'] ?? null, ['terminated', 'cancelled'])) {
            continue;
        }

        // 🔥 DATA DE CANCELAMENTO CORRETA
        $cancelDate = $prev['data_anulacao'] ?? null;

        if (!$cancelDate && in_array($prev['estado_apolice'], ['cancelled', 'terminated'])) {
            $cancelDate = $prev['data_fim'];
        }

        if (!$cancelDate || !$prev['data_inicio'] || !$curr['data_inicio']) {
            continue;
        }

        // 🔥 duração real da apólice
        $duration = $this->safeDays($prev['data_inicio'], $cancelDate);

        // 🔒 só interessa cancelamento precoce
        if ($duration === null || $duration > 30) {
            continue;
        }

        // 🔥 DATA DE SUBSTITUIÇÃO (CRÍTICO AML)
        $replacementDays = $this->safeDays($cancelDate, $curr['data_inicio']);

        if ($replacementDays === null) {
            continue;
        }

        // 🔥 regra principal: substituição rápida (<= 7 dias)
        if ($replacementDays <= 7) {

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

    usort($chains, fn($a, $b) => count($b) - count($a));
    $chain = $chains[0];

    // 🔹 últimos 12 meses
    $chain = array_filter($chain, function ($p) {
        return isset($p['data_inicio']) &&
            Carbon::parse($p['data_inicio'])->gte(now()->subYear());
    });

    if (count($chain) < 3) return;

    $chain = array_slice($chain, -20);

    $apolices = [];
    $replacementTimeline = [];
    $earlyCancels = 0;

    for ($i = 1; $i < count($chain); $i++) {

        $prev = $chain[$i - 1];
        $curr = $chain[$i];

        // 🔥 cancelamento correto
        $cancelDate = $prev['data_anulacao'] ?? $prev['data_fim'];

        // 🔥 substituição real
        $replacementDays = $this->safeDays($cancelDate, $curr['data_inicio']);

        $apolices[] = $prev['numero_apolice'] . ' → ' . $curr['numero_apolice'];

        $replacementTimeline[] = sprintf(
            "%s (%s → %s = %d dias)",
            $prev['numero_apolice'],
            $cancelDate,
            $curr['data_inicio'],
            $replacementDays
        );

        if ($this->safeDays($prev['data_inicio'], $cancelDate) <= 30) {
            $earlyCancels++;
        }
    }

    // 🔥 SCORE AML
    $score = 15;

    if ($earlyCancels >= 2) $score += 5;
    if (count($chain) >= 5) $score += 5;

    $description = sprintf(
        "\n" .
        "Cliente: %s\n\n" .
        "Cadeia de substituição:\n%s\n\n" .
        "Timeline de substituições:\n%s\n\n" .
        "Eventos: %d\n" .
        "Cancelamentos precoces (<30 dias): %d\n\n" .
        "Indicador AML: Substituição rápida de apólices com possível ocultação de fundos (layering acelerado)",
        $customer->customer_number,
        implode(', ', $apolices),
        implode("\n", $replacementTimeline),
        count($chain),
        $earlyCancels
    );

    $this->createAlert(
        $customer,
        'Substituição ou cancelamento repetido',
        $description,
        'Alto',
        $score
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