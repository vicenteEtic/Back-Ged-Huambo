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
    $valid = [];

    // 🔥 normalização robusta de datas
    foreach ($policies as $p) {

        $start = $this->safeDate($p['data_inicio'] ?? null);
        $end   = $this->safeDate($p['data_fim'] ?? null);

        if (!$start || !$end) continue;

        $days = $start->diffInDays($end);

        // 🔥 3-6 meses
        if ($days >= 90 && $days <= 180 && ($p['premium_total'] ?? 0) > 0) {
            $valid[] = $p;
        }
    }

    if (count($valid) < 3) return;

    usort($valid, fn($a, $b) =>
        strtotime($a['data_inicio']) <=> strtotime($b['data_inicio'])
    );

    $window = [];
    $totalPremium = 0;
    $earlyCancels = 0;

    foreach ($valid as $p) {

        $window[] = $p;
        $totalPremium += $p['premium_total'];

        $start = $this->safeDate($p['data_inicio']);
        $end   = $this->safeDate($p['data_fim']);

        if ($start && $end && $start->diffInDays($end) < 180) {
            $earlyCancels++;
        }
    }

    if (count($window) < 3 || $totalPremium < 300000) return;

    $apolices = array_column($window, 'numero_apolice');

    $description = sprintf(
        "RELATÓRIO KYT - MÚLTIPLAS APÓLICES DE CURTA DURAÇÃO\n\n" .
        "Cliente: %s\n\n" .
        "Resumo do comportamento:\n" .
        "- Total de apólices analisadas: %d\n" .
        "- Apólices identificadas: %s\n" .
        "- Valor total acumulado: %s\n" .
        "- Cancelamentos antecipados (<180 dias): %d\n\n" .
        "Interpretação AML:\n" .
        "Foi identificado um padrão de fragmentação de contratos através de múltiplas apólices de curta duração.\n" .
        "Este comportamento pode indicar tentativa de dispersão de valores (smurfing) ou estruturação de operações financeiras.\n\n" .
        "Período analisado: %s → %s",
        $customer->customer_number,
        count($window),
        implode(', ', $apolices),
        $this->formatMoney($totalPremium),
        $earlyCancels,
        $window[0]['data_inicio'],
        end($window)['data_inicio']
    );

    $score = 15;

    if ($totalPremium >= 500000) $score += 5;
    if (count($window) >= 5) $score += 5;
    if ($earlyCancels >= 2) $score += 5;

    $this->createAlert(
        $customer,
        'Churn de apólices (trocas frequentes)',
        $description,
        'Médio',
        $score
    );
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
    usort($policies, fn($a, $b) =>
        strtotime($a['data_inicio'] ?? '1970') <=> strtotime($b['data_inicio'] ?? '1970')
    );

    $chains = [];
    $current = [];

    for ($i = 1; $i < count($policies); $i++) {

        $prev = $policies[$i - 1];
        $curr = $policies[$i];

        // 🔥 cancelamento real
        $cancelDate = $this->safeDate(
            $prev['data_anulacao'] ?? $prev['data_fim'] ?? null
        );

        $currStart = $this->safeDate($curr['data_inicio'] ?? null);

        if (!$cancelDate || !$currStart) continue;

        // 🔥 duração da apólice
        $startPrev = $this->safeDate($prev['data_inicio'] ?? null);
        if (!$startPrev) continue;

        $duration = $startPrev->diffInDays($cancelDate);

        if ($duration > 30) continue;

        // 🔥 substituição rápida
        $gap = $cancelDate->diffInDays($currStart);

        if ($gap <= 7) {
            $current[] = $prev;
            $current[] = $curr;
        } else {
            if (count($current) >= 3) {
                $chains[] = $current;
            }
            $current = [];
        }
    }

    if (count($current) >= 3) {
        $chains[] = $current;
    }

    if (empty($chains)) return;

    usort($chains, fn($a, $b) => count($b) <=> count($a));
    $chain = $chains[0];

    $chain = array_filter($chain, function ($p) {
        return isset($p['data_inicio']) &&
            Carbon::parse($p['data_inicio'])->gte(now()->subYear());
    });

    if (count($chain) < 3) return;

    $apolices = [];
    $early = 0;

    for ($i = 1; $i < count($chain); $i++) {

        $prev = $chain[$i - 1];
        $curr = $chain[$i];

        $cancelDate = $this->safeDate($prev['data_anulacao'] ?? $prev['data_fim']);
        $currStart = $this->safeDate($curr['data_inicio']);

        $gap = $cancelDate->diffInDays($currStart);

        $apolices[] = $prev['numero_apolice'] . " → " . $curr['numero_apolice'];

        if ($gap <= 7) $early++;
    }

    $description = sprintf(
        "RELATÓRIO KYT - SUBSTITUIÇÃO RÁPIDA DE APÓLICES\n\n" .
        "Cliente: %s\n\n" .
        "Resumo do comportamento:\n" .
        "- Cadeia de substituição identificada com %d eventos\n" .
        "- Substituições ocorreram em intervalo ≤ 7 dias\n" .
        "- Cancelamentos rápidos (<30 dias): %d\n\n" .
        "Apólices envolvidas:\n%s\n\n" .
        "Interpretação AML:\n" .
        "Existe um padrão consistente de cancelamento e re-subscrição de apólices em curto espaço de tempo.\n" .
        "Este comportamento é típico de técnicas de layering utilizadas para dificultar rastreamento de fundos e beneficiários.\n\n" .
        "Período analisado: últimos 12 meses",
        $customer->customer_number,
        count($chain),
        $early,
        implode(', ', $apolices)
    );

    $score = 20;

    if ($early >= 2) $score += 5;
    if (count($chain) >= 5) $score += 5;

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