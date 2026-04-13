<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Models\Entities\RiskAssessment;
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

        Log::info("KYT START", [
            'customer' => $customer->customer_number,
            'policies' => count($policies)
        ]);

        if (empty($policies)) return;

        $this->checkFrequentBeneficiaryChanges($customer, $beneficiaries);

        Log::info("KYT FINISHED", [
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

        Log::info('📥 RAW BENEFICIARIES SAMPLE', [
            'sample' => array_slice($beneficiaries, 0, 2)
        ]);

        $grouped = collect($beneficiaries)
            ->map(function ($b) {

                $b = (array) $b;

                $mapped = [
                    'numero_apolice' => trim($b['numero_apolice'] ?? ''),
                    'descricao_produto' => strtoupper(trim($b['descricao_produto'] ?? 'UNKNOWN_PRODUCT')),
                    'codigo_beneficiario' => trim($b['codigo_beneficiario'] ?? ''),
                    'nome_beneficiario' => strtoupper(trim($b['nome_beneficiario'] ?? '')),
                    'tipo_beneficiario' => strtoupper(trim($b['tipo_beneficiario'] ?? '')),
                    'data' => $b['data_atualizacao_beneficiario'] ?? null,
                ];

                return $mapped;
            })
            ->filter(function ($b) {

                $valid = !empty($b['numero_apolice']);

                if (!$valid) {
                    Log::warning('❌ FILTERED OUT BENEFICIARY (NO APOLICE)', $b);
                }

                return $valid;
            })
            ->groupBy('descricao_produto');

        Log::info('📊 GROUPS CREATED', [
            'total_groups' => $grouped->count(),
            'groups' => $grouped->keys()
        ]);

        foreach ($grouped as $produto => $records) {

            Log::info('🔎 PROCESSING PRODUCT GROUP', [
                'produto' => $produto,
                'records' => $records->count()
            ]);

            $records = $records
                ->sortBy(fn($r) => $this->safeDate($r['data'])?->timestamp ?? 0)
                ->values();

            if ($records->count() < 2) {
                Log::info('⛔ SKIP GROUP (INSUFFICIENT RECORDS)', [
                    'produto' => $produto
                ]);
                continue;
            }

            $history = [];
            $changes = 0;
            $prev = null;

            foreach ($records as $r) {

                $beneficiaryId = $r['codigo_beneficiario']
                    ?: md5($r['nome_beneficiario'] . '|' . $r['tipo_beneficiario']);

                Log::debug('👤 BENEFICIARY STEP', [
                    'apolice' => $r['numero_apolice'],
                    'beneficiary' => $beneficiaryId,
                    'date' => $r['data']
                ]);

                if (!$beneficiaryId) {
                    Log::warning('❌ INVALID BENEFICIARY ID', $r);
                    continue;
                }

                $history[] = $beneficiaryId;

                if ($prev !== null && $prev !== $beneficiaryId) {
                    $changes++;

                    Log::warning('🔁 BENEFICIARY CHANGE DETECTED', [
                        'from' => $prev,
                        'to' => $beneficiaryId,
                        'changes' => $changes
                    ]);
                }

                $prev = $beneficiaryId;
            }

            $unique = count(array_unique($history));

            Log::info('📈 BENEFICIARY ANALYSIS RESULT', [
                'produto' => $produto,
                'unique_beneficiaries' => $unique,
                'changes' => $changes
            ]);

            if ($unique < 3 || $changes < 2) {
                Log::info('⛔ KYT RULE NOT MET', [
                    'produto' => $produto,
                    'unique' => $unique,
                    'changes' => $changes
                ]);
                continue;
            }

            $dates = collect($records)
                ->map(fn($r) => $this->safeDate($r['data']))
                ->filter();

            if ($dates->isEmpty()) {
                Log::warning('❌ NO VALID DATES FOUND', [
                    'produto' => $produto
                ]);
                continue;
            }

            $min = $dates->min();
            $max = $dates->max();
            $days = $min->diffInDays($max);

            Log::info('📅 TIME WINDOW ANALYSIS', [
                'produto' => $produto,
                'start' => $min,
                'end' => $max,
                'days' => $days
            ]);

            if ($days > 365) {
                Log::info('⛔ TIME WINDOW TOO LARGE (SKIP)', [
                    'produto' => $produto,
                    'days' => $days
                ]);
                continue;
            }

            /**
             * =========================
             * SCORE KYT
             * =========================
             */
            $score = 20;

            if ($changes >= 3) $score += 10;
            if ($changes >= 4) $score += 15;
            if ($changes >= 5) $score += 20;

            if ($unique >= 3) $score += 10;
            if ($unique >= 4) $score += 15;

            Log::warning('🚨 KYT ALERT TRIGGERED', [
                'produto' => $produto,
                'score' => $score,
                'unique' => $unique,
                'changes' => $changes
            ]);

            $this->createAlert(
                $customer,
                'KYT_FREQUENT_BENEFICIARY_CHANGES',
                "KYT detectado no produto {$produto}",
                'Alto',
                $score
            );
        }

        Log::info('🏁 KYT PRODUCT BENEFICIARY ANALYSIS FINISHED');
    }
    /* =========================
       ALERT CREATION
    ========================== */

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

            Log::warning("ALERT {$type}", [
                'customer' => $customer->customer_number
            ]);
        }
    }
}
