<?php

namespace App\Http\Controllers\KYT;

use App\Http\Controllers\AbstractController;
use App\Services\KYT\KytRuleService;
use App\Http\Requests\KYT\KytRuleRequest;
use App\Models\KYT\KytRule;
use Illuminate\Http\Request;

class KytRuleController extends AbstractController
{
    public function __construct(KytRuleService $service)
    {
        $this->service = $service;
        $this->nameEntity = 'KYT Rule';
        $this->fieldName = 'name';
        $this->logType = 'kyt_rules';
    }

    public function index(Request $request)
    {
        $scenarioNames = config('kyt.scenario_names', []);
        $slugs = array_keys($scenarioNames);

        $rules = KytRule::with('products')
            ->whereIn('slug', $slugs)
            ->get()
            ->groupBy('slug');

        $result = [];
        foreach ($slugs as $slug) {
            $scenario = [
                'slug' => $slug,
                'name' => $scenarioNames[$slug],
                'description' => null,
                'rules' => [],
            ];

            if (isset($rules[$slug])) {
                $group = $rules[$slug];
                $first = $group->first();
                $scenario['description'] = $first->interpretation_aml;

                $scenario['rules'] = $group->map(function ($rule) {
                    $description = $rule->interpretation_aml;

                    $parts = [];
                    if ($rule->threshold_field && $rule->threshold_value) {
                        $parts[] = 'Limiar: ' . number_format($rule->threshold_value, 0, ',', '.') . ' Kz (' . $rule->threshold_field . ')';
                    }
                    if ($rule->min_events) {
                        $parts[] = 'Eventos mínimos: ' . $rule->min_events;
                    }
                    if ($rule->max_days) {
                        $parts[] = 'Janela: ' . $rule->max_days . ' dias';
                    }
                    if (!empty($parts)) {
                        $description .= "\nParâmetros aplicados: " . implode(' | ', $parts);
                    }

                    return [
                        'id' => $rule->id,
                        'entity_type' => $rule->entity_type,
                        'name' => $rule->name,
                        'description' => $description,
                        'threshold_field' => $rule->threshold_field,
                        'threshold_value' => $rule->threshold_value,
                        'min_events' => $rule->min_events,
                        'max_days' => $rule->max_days,
                        'score_base' => $rule->score_base,
                        'score_increments' => $rule->score_increments,
                        'severity' => $rule->severity,
                        'extra_params' => $rule->extra_params,
                        'is_active' => $rule->is_active,
                        'relevant_products' => $rule->relevantProducts(),
                        'excluded_products' => $rule->excludedProducts(),
                    ];
                })->values()->all();
            }

            $result[] = $scenario;
        }

        return response()->json($result);
    }

    public function store(KytRuleRequest $request)
    {
        try {
            $this->logRequest();
            $rule = $this->service->store($request->validated());
            return response()->json($rule, \Illuminate\Http\Response::HTTP_CREATED);
        } catch (\Exception $e) {
            $this->logRequest($e);
            \Illuminate\Support\Facades\Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro interno no servidor.'], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function update(KytRuleRequest $request, $id)
    {
        try {
            $this->logRequest();
            $rule = $this->service->update($request->validated(), $id);
            return response()->json($rule, \Illuminate\Http\Response::HTTP_OK);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            $this->logRequest($e);
            return response()->json(['error' => 'Resource not found.'], \Illuminate\Http\Response::HTTP_NOT_FOUND);
        } catch (\Exception $e) {
            $this->logRequest($e);
            \Illuminate\Support\Facades\Log::error('Erro interno', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return response()->json(['error' => 'Erro interno no servidor.'], \Illuminate\Http\Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
