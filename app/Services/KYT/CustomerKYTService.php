<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
use App\Models\Indicator\IndicatorType;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Carbon\Carbon;

class CustomerKYTService
{
    public $timeout = 100;
    public $tries = 8;
    public $backoff = 10;

    /* =========================
       RISK
    ========================== */

    public function RiskAssessmentEntity(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $risk = RiskAssessment::where('entity_id', $customer->id)->latest()->first();

        $data = [
            'risk_id' => $risk->id ?? null,
            'alert_priority' => $risk ? in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]) : false,
            'valid' => (bool) $risk
        ];

        Cache::put($cacheKey, $data, now()->addHours(20));

        return $data;
    }

    /* =========================
       ENTRY POINT
    ========================== */

    public function runAllChecksMemory(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ): void {

        Log::info("🚀 KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        if (empty($policies)) return;

        //  $this->checkFrequentBeneficiaryChanges($customer, $beneficiaries);

        $this->checkHighRiskGeography(
            $customer,
            $policies,
            $receipts,
            $beneficiaries
        );

        Log::info("🏁 KYT FINISHED", [
            'customer' => $customer->customer_number
        ]);
    }

    /* =========================
       SAFE DATE
    ========================== */

    private function safeDate($date): ?Carbon
    {
        try {
            if (!$date) return null;
            return Carbon::parse($date);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /* =========================
       KYT RULE
    ========================== */

    private function checkFrequentBeneficiaryChanges(
        Entities $customer,
        array $beneficiaries = []
    ): void {

        Log::info('🚀 KYT PRODUCT BENEFICIARY ANALYSIS START', [
            'customer' => $customer->customer_number,
            'records_received' => count($beneficiaries)
        ]);

        if (empty($beneficiaries)) {
            Log::warning('⚠️ KYT EXIT - EMPTY BENEFICIARIES');
            return;
        }

        /* =========================
           NORMALIZAÇÃO CRÍTICA
        ========================== */

        $beneficiaries = collect($beneficiaries)
            ->map(function ($b) {

                $b = (array) $b;

                return [
                    'numero_apolice' => trim((string)($b['numero_apolice'] ?? '')),
                    'descricao_produto' => strtoupper(trim($b['descricao_produto'] ?? 'UNKNOWN')),
                    'codigo_beneficiario' => trim((string)($b['codigo_beneficiario'] ?? '')),
                    'nome_beneficiario' => strtoupper(trim($b['nome_beneficiario'] ?? '')),
                    'tipo_beneficiario' => strtoupper(trim($b['tipo_beneficiario'] ?? '')),
                    'percentagem_atribuida' => (float)($b['percentagem_atribuida'] ?? 0),
                    'data' => $b['data_atualizacao_beneficiario'] ?? null,
                ];
            })
            ->filter(fn($b) => $b['numero_apolice'] !== '')
            ->values();

        Log::info('📦 NORMALIZED BENEFICIARIES', [
            'count' => $beneficiaries->count()
        ]);

        /* =========================
           GROUP BY PRODUCT
        ========================== */

        $grouped = $beneficiaries->groupBy('descricao_produto');

        Log::info('📊 GROUPS CREATED', [
            'total_groups' => $grouped->count(),
            'products' => $grouped->keys()
        ]);

        foreach ($grouped as $produto => $records) {

            Log::info('🔎 PROCESSING PRODUCT', [
                'produto' => $produto,
                'records' => $records->count()
            ]);

            if ($records->count() < 2) {
                Log::info('⛔ SKIP PRODUCT (INSUFFICIENT DATA)', [
                    'produto' => $produto
                ]);
                continue;
            }

            $records = $records->sortBy(
                fn($r) =>
                $this->safeDate($r['data'])?->timestamp ?? 0
            )->values();

            $history = [];
            $changes = 0;
            $prev = null;

            foreach ($records as $r) {

                $beneficiaryId = $r['codigo_beneficiario']
                    ?: md5($r['nome_beneficiario'] . $r['tipo_beneficiario']);

                $history[] = $beneficiaryId;

                if ($prev !== null && $prev !== $beneficiaryId) {
                    $changes++;
                }

                $prev = $beneficiaryId;
            }

            $unique = count(array_unique($history));

            Log::info('📈 ANALYSIS RESULT', [
                'produto' => $produto,
                'unique_beneficiaries' => $unique,
                'changes' => $changes
            ]);

            if ($unique < 3 || $changes < 2) {
                Log::info('⛔ RULE NOT TRIGGERED', [
                    'produto' => $produto
                ]);
                continue;
            }

            $dates = $records->map(
                fn($r) =>
                $this->safeDate($r['data'])
            )->filter();

            if ($dates->isEmpty()) {
                Log::warning('❌ NO VALID DATES', [
                    'produto' => $produto
                ]);
                continue;
            }

            $min = $dates->min();
            $max = $dates->max();
            $days = $min->diffInDays($max);

            if ($days > 365) {
                Log::info('⛔ TIME RANGE EXCEEDED', [
                    'produto' => $produto,
                    'days' => $days
                ]);
                continue;
            }









            /* =========================
               SCORE KYT
            ========================== */

            $score = 20;

            if ($changes >= 3) $score += 10;
            if ($changes >= 4) $score += 15;
            if ($changes >= 5) $score += 20;

            if ($unique >= 3) $score += 10;
            if ($unique >= 4) $score += 15;

            Log::warning('🚨 KYT ALERT TRIGGERED', [
                'produto' => $produto,
                'score' => $score
            ]);
            $apolicesDetalhadas = $records
                ->groupBy('numero_apolice')
                ->map(function ($items, $apolice) {
                    return "- Apólice: {$apolice} | Registos: " . $items->count();
                })
                ->implode("\n");

            $beneficiaryList = collect($records)
                ->map(function ($r) {
                    return "- Nome: {$r['nome_beneficiario']}
  Tipo: {$r['tipo_beneficiario']}
  ID Beneficiário: {$r['codigo_beneficiario']}
  Apólice: {$r['numero_apolice']}
  Percentagem: {$r['percentagem_atribuida']}%";
                })
                ->implode("\n\n");

            $apolicesUnicas = $records
                ->pluck('numero_apolice')
                ->unique()
                ->implode(', ');

            $description = "
KYT - ALTERAÇÃO FREQUENTE DE BENEFICIÁRIOS

Cliente: {$customer->customer_number}
Produto: {$produto}

 APÓLICES ENVOLVIDAS:
{$apolicesDetalhadas}

 RESUMO GLOBAL:
- Apólices afetadas: {$apolicesUnicas}
- Beneficiários distintos: {$unique}
- Número de alterações: {$changes}
- Período analisado: {$min->format('Y-m-d')} → {$max->format('Y-m-d')}
- Duração: {$days} dias

 BENEFICIÁRIOS IDENTIFICADOS:
{$beneficiaryList}

⚠️ ANÁLISE DE RISCO:
Foi identificado um padrão de alterações de beneficiários distribuído por múltiplas apólices do mesmo produto.
Este comportamento pode indicar reorganização de beneficiários ou tentativa de diluição de beneficiário final (UBO).

";

            $this->createAlert(
                $customer,
                'KYT_FREQUENT_BENEFICIARY_CHANGES',
                $description,
                'Alto',
                $score
            );
        }

        Log::info('🏁 KYT PRODUCT BENEFICIARY ANALYSIS FINISHED');
    }


    private function checkHighRiskGeography(
        Entities $customer,
        array $policies,
        array $receipts = [],
        array $beneficiaries = []
    ): void {
    
        Log::info('🌍 KYT HIGH RISK GEOGRAPHY START', [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies),
            'receipts_count' => count($receipts),
            'beneficiaries_count' => count($beneficiaries)
        ]);
    
        /* =========================
           NORMALIZAÇÃO
        ========================== */
    
        $policies = collect($policies)->map(fn($p) => (array) $p);
        $beneficiaries = collect($beneficiaries)->map(fn($b) => (array) $b);
        $receipts = collect($receipts)->map(fn($r) => (array) $r);
    
        /* =========================
           LISTA DINÂMICA
        ========================== */
    
        $highRiskCountries = IndicatorType::where('indicator_id', 9)
            ->where('score', '>=', 3)
            ->pluck('description')
            ->map(fn($c) => strtoupper(trim($c)))
            ->toArray();
    
        Log::info('📌 HIGH RISK COUNTRIES LOADED', [
            'count' => count($highRiskCountries),
            'countries' => $highRiskCountries
        ]);
    
        foreach ($policies as $policy) {
    
            $apolice = $policy['numero_apolice'] ?? null;
    
            if (!$apolice) {
                Log::warning('⚠️ POLICY WITHOUT APOLICE NUMBER', ['policy' => $policy]);
                continue;
            }
    
            Log::info('🔎 PROCESSING APOLICE', [
                'apolice' => $apolice
            ]);
    
            $countriesDetected = [];
    
            /* =========================
               BENEFICIÁRIOS
            ========================== */
    
            $beneficiariosApolice = $beneficiaries->where('numero_apolice', $apolice);
    
            Log::info('👤 BENEFICIARIES FOUND', [
                'apolice' => $apolice,
                'count' => $beneficiariosApolice->count()
            ]);
    
            foreach ($beneficiariosApolice as $b) {
    
                $pais = $this->normalizeCountry($b['pais_residencia_beneficiario'] ?? null);
    
                if ($pais) {
                    $countriesDetected[] = $pais;
    
                    Log::info('🌍 BENEFICIARY COUNTRY DETECTED', [
                        'apolice' => $apolice,
                        'country' => $pais
                    ]);
                } else {
                    Log::warning('⚠️ BENEFICIARY WITHOUT COUNTRY', [
                        'apolice' => $apolice,
                        'data' => $b
                    ]);
                }
            }
    
            /* =========================
               RECIBOS
            ========================== */
    
            $recibosApolice = $receipts->where('numero_apolice', $apolice);
    
            Log::info('💰 RECEIPTS FOUND', [
                'apolice' => $apolice,
                'count' => $recibosApolice->count()
            ]);
    
            foreach ($recibosApolice as $r) {
    
                $pais = $this->normalizeCountry($r['pais_iban_origem'] ?? null);
    
                if (!$pais && !empty($r['iban_origem'])) {
                    $pais = $this->extractCountryFromIBAN($r['iban_origem']);
    
                    Log::info('🏦 COUNTRY FROM IBAN', [
                        'iban' => $r['iban_origem'],
                        'country' => $pais
                    ]);
                }
    
                if ($pais) {
                    $countriesDetected[] = $pais;
    
                    Log::info('💰 RECEIPT COUNTRY DETECTED', [
                        'apolice' => $apolice,
                        'country' => $pais
                    ]);
                } else {
                    Log::warning('⚠️ RECEIPT WITHOUT COUNTRY', [
                        'apolice' => $apolice,
                        'data' => $r
                    ]);
                }
            }
    
            /* =========================
               RESULTADO DETECÇÃO
            ========================== */
    
            $countriesDetected = array_unique($countriesDetected);
    
            Log::info('📊 COUNTRIES DETECTED FINAL', [
                'apolice' => $apolice,
                'countries' => $countriesDetected
            ]);
    
            if (empty($countriesDetected)) {
                Log::warning('⛔ NO COUNTRIES DETECTED - SKIPPING', [
                    'apolice' => $apolice
                ]);
                continue;
            }
    
            /* =========================
               MATCH RISCO
            ========================== */
    
            $riskCountries = array_intersect($countriesDetected, $highRiskCountries);
    
            Log::info('⚠️ RISK MATCH RESULT', [
                'apolice' => $apolice,
                'risk_countries' => $riskCountries
            ]);
    
            if (empty($riskCountries)) {
                Log::warning('⛔ NO HIGH RISK MATCH - NO ALERT GENERATED', [
                    'apolice' => $apolice,
                    'detected' => $countriesDetected,
                    'risk_list' => $highRiskCountries
                ]);
                continue;
            }
    
            /* =========================
               SCORE
            ========================== */
    
            $score = 25;
    
            Log::info('🚨 ALERT TRIGGERED', [
                'apolice' => $apolice,
                'score' => $score,
                'risk_countries' => $riskCountries
            ]);
    
            /* =========================
               ALERTA
            ========================== */
            $description = sprintf(
                "KYT - HIGH RISK GEOGRAPHY (ANÁLISE AML/KYT)
                
                IDENTIFICAÇÃO DO CLIENTE
                - Cliente: %s
                - Apólice: %s
                
                EXPOSIÇÃO GEOGRÁFICA DETECTADA
                - Países identificados nas operações: %s
                - Países classificados como alto risco: %s
                
                FONTES DE DADOS
                - Beneficiários da apólice (país de residência declarado)
                - Fluxos financeiros (recibos e pagamentos)
                - Origem de fundos via IBAN (quando aplicável)
                
                ANÁLISE DE RISCO
                Foi identificado envolvimento direto ou indireto com jurisdições classificadas como de risco elevado, através de:
                - Beneficiários associados a países sensíveis
                - Movimentações financeiras com origem externa potencialmente não justificadas
                - Incongruência entre perfil do cliente e exposição internacional
                
                INTERPRETAÇÃO AML
                Este padrão pode indicar tentativa de:
                - Estruturação de fundos através de jurisdições de baixo controlo regulatório
                - Integração de capitais via reembolsos ou pagamentos internacionais
                - Ocultação de beneficiário efetivo (UBO)
                
                REFERÊNCIAS REGULATÓRIAS
                - GAFI/FATF: jurisdições de alto risco e não cooperantes
                - ARSEG: transações internacionais sem racional económico claro como red flag
                
                CLASSIFICAÇÃO
                - Tipologia: KYT_HIGH_RISK_GEOGRAPHY
                - Severidade: ALTA
                - Recomendação: Submissão para análise reforçada (EDD) e possível STR à UIF
                
                DETALHE OPERACIONAL
                - Sistema: KYT Automated Monitoring Engine
                - Data de análise: %s
                ",
                $customer->customer_number,
                $apolice,
                implode(', ', $countriesDetected),
                implode(', ', $riskCountries),
                now()->format('Y-m-d H:i:s')
                );
    
            $this->createAlert(
                $customer,
                'KYT_HIGH_RISK_GEOGRAPHY',
                $description,
                'Alto',
                $score
            );
        }
    
        Log::info('🏁 KYT HIGH RISK GEOGRAPHY FINISHED');
    }

    /* =========================
       ALERT CREATION
    ========================== */
    private function extractCountryFromIBAN(string $iban): ?string
    {
        $iban = strtoupper(trim($iban));

        if (strlen($iban) < 2) return null;

        return substr($iban, 0, 2); // ex: AO, PT, GB
    }
    private function formatMoney($value): string
    {
        return number_format((float)$value, 2, '.', ' ');
    }
    private function normalizeCountry(?string $country): ?string
    {
        if (!$country) return null;

        return strtoupper(trim($country));
    }
    private function createAlert(
        Entities $customer,
        string $type,
        string $description,
        string $severity,
        int $score
    ): void {

        $risk = $this->RiskAssessmentEntity($customer);

        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => $description,
            ],
            [
                'alert_priority' => $risk['alert_priority'],
                'risk_assessment_id' => $risk['risk_id'],
                'category' => 'KYT',
                'level' => $severity,
                'name' => $customer->social_denomination,
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated || $alert->wasChanged()) {
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))
                ->onQueue('high');

            Log::warning("ALERT CREATED", [
                'type' => $type,
                'customer' => $customer->customer_number
            ]);
        }
    }
}
