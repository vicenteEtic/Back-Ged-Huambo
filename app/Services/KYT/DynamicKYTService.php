<?php

namespace App\Services\KYT;

use App\Models\Entities\Entities;
use App\Models\KYT\KytRule;
use App\Models\Alert\Alert;
use App\Jobs\SendGrupoAlertEmailJob;
use App\Services\KYT\Rules\Contracts\RuleHandler;
use App\Services\KYT\Rules\DefaultRuleHandler;
use App\Models\Entities\RiskAssessment;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use App\Services\KYT\PepSanctionCheckService;

class DynamicKYTService
{
    private array $handlerCache = [];

    public function runAllChecksMemory(
        Entities $customer,
        array $policies,
        array $changes = [],
        array $refunds = [],
        array $receipts = [],
        array $beneficiaries = []
    ): void
    {
        $policies = $this->normalizePolicies($policies);

        Log::info("KYT (dynamic) START", [
            'customer' => $customer->customer_number,
            'policies_count' => count($policies),
        ]);

        if (empty($policies)) return;

        $customerType = ((int)($customer->entity_type ?? 0)) === \App\Enum\TypeEntity::COLECTIVA->value
            ? 'collective'
            : 'individual';

        $rules = $this->loadActiveRules();

        $pepService = app(PepSanctionCheckService::class);
        $additionalNames = array_merge(
            $pepService->extractPayerNames($receipts),
            $pepService->extractBeneficiaryNames($beneficiaries)
        );

        foreach ($rules as $rule) {
            if ($rule->entity_type !== 'both' && $rule->entity_type !== $customerType) continue;

            $handler = $this->resolveHandler($rule);

            $results = $handler->check(
                $customer,
                $rule,
                $policies,
                $changes,
                $refunds,
                $receipts,
                $beneficiaries,
            );

            $scenarioNames = config('kyt.scenario_names', []);
            $fixedName = $scenarioNames[$rule->slug] ?? $rule->name;

            foreach ($results as $result) {
                $this->createAlert($customer, $fixedName, $result['description'], $result['severity'], $result['score'], $additionalNames);
            }
        }

        Log::info("KYT (dynamic) FINISHED", ['customer' => $customer->customer_number]);
    }

    private function loadActiveRules(): array
    {
        if (!Schema::hasTable('kyt_rule_definitions')) {
            return [];
        }

        $cacheKey = 'kyt_active_rule_definitions';

        return Cache::remember($cacheKey, now()->addHour(), function () {
            return KytRule::with('products')
                ->where('is_active', true)
                ->get()
                ->all();
        });
    }

    public function clearRulesCache(): void
    {
        Cache::forget('kyt_active_rules');
    }

    private function resolveHandler(KytRule $rule): RuleHandler
    {
        if (isset($this->handlerCache[$rule->slug])) {
            return $this->handlerCache[$rule->slug];
        }

        $handlerClass = $this->findHandlerClass($rule->slug);

        $handler = new $handlerClass();
        $this->handlerCache[$rule->slug] = $handler;

        return $handler;
    }

    private function findHandlerClass(string $slug): string
    {
        $map = config('kyt.handlers', []);

        return $map[$slug] ?? DefaultRuleHandler::class;
    }

    private function normalizePolicies(array $policies): array
    {
        $normalized = [];
        foreach ($policies as $p) {
            if (is_object($p)) {
                $normalized[] = (array) $p;
            } else {
                $normalized[] = $p;
            }
        }
        return $normalized;
    }

    private function createAlert(
        Entities $customer,
        string $type,
        string $description,
        string $severity,
        int $score,
        array $additionalNames = []
    ): void
    {
        $riskData = $this->riskAssessment($customer);

        $pepService = app(PepSanctionCheckService::class);

        $namesToCheck = [$customer->social_denomination];
        foreach ($additionalNames as $n) {
            $n = trim($n ?? '');
            if (!empty($n)) $namesToCheck[] = $n;
        }
        $namesToCheck = array_unique(array_filter($namesToCheck));
        $namesToCheck = array_values($namesToCheck);

        $findings = $pepService->checkMultiple($namesToCheck);

        $finalDescription = $description;
        $alertPriority = $riskData['alert_priority'];

        if (!empty($findings)) {
            $finalDescription .= $pepService->buildDescriptionSuffix($findings);
            $alertPriority = true;
        }

        $alert = Alert::updateOrCreate(
            [
                'entity_id' => $customer->id,
                'type' => $type,
                'description' => $finalDescription,
            ],
            [
                'alert_priority' => $alertPriority,
                'risk_assessment_id' => $riskData['risk_id'],
                'category' => 'KYT',
                'level' => $severity,
                'name' => $type,
                'score' => $score,
            ]
        );

        if ($alert->wasRecentlyCreated || $alert->wasChanged()) {
            SendGrupoAlertEmailJob::dispatch($alert->id, config('app.url'))->onQueue('high');
            Log::warning("ALERTA {$type}", [
                'cliente' => $customer->customer_number,
                'descricao' => $finalDescription,
            ]);
        }
    }

    private function riskAssessment(Entities $customer): array
    {
        $cacheKey = "risk_assessment_entity_{$customer->id}";

        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $risk = RiskAssessment::where('entity_id', $customer->id)
            ->latest()
            ->first();

        if (!$risk) {
            $data = ['risk_id' => null, 'alert_priority' => false, 'valid' => false];
            Cache::put($cacheKey, $data, now()->addHours(20));
            return $data;
        }

        $isHighRisk = in_array($risk->diligence, ["Cliente Inaceitável", "Reforçada"]);

        $data = [
            'risk_id' => $risk->id,
            'alert_priority' => $isHighRisk,
            'valid' => !$isHighRisk,
        ];

        Cache::put($cacheKey, $data, now()->addHours(20));

        return $data;
    }
}
