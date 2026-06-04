<?php

namespace App\Http\Requests\KYT;

use App\Http\Requests\BaseFormRequest;

class KytRuleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $ruleId = $this->route('kyt_rule');
        $entityType = $this->input('entity_type');

        $uniqueSlug = 'unique:kyt_rule_definitions,slug';
        if ($ruleId && $entityType) {
            $uniqueSlug .= ',' . $ruleId . ',id,entity_type,' . $entityType;
        } elseif ($ruleId) {
            $uniqueSlug .= ',' . $ruleId;
        }

        return [
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z_]+$/', $uniqueSlug],
            'name' => ['required', 'string', 'max:255'],
            'entity_type' => ['required', 'string', 'in:individual,collective,both'],
            'threshold_field' => ['nullable', 'string', 'max:100'],
            'threshold_value' => ['nullable', 'numeric', 'min:0'],
            'min_events' => ['nullable', 'integer', 'min:0'],
            'max_days' => ['nullable', 'integer', 'min:0'],
            'score_base' => ['required', 'integer', 'min:0', 'max:100'],
            'score_increments' => ['nullable', 'array'],
            'severity' => ['required', 'string', 'in:Alto,Médio,Baixo'],
            'description_template' => ['required', 'string'],
            'interpretation_aml' => ['required', 'string'],
            'extra_params' => ['nullable', 'array'],
            'is_active' => ['nullable', 'boolean'],
            'products' => ['nullable', 'array'],
            'products.*.product_name' => ['required_with:products', 'string', 'max:255'],
            'products.*.type' => ['required_with:products', 'string', 'in:relevant,excluded'],
        ];
    }

    public function attributes(): array
    {
        return [
            'slug' => 'Slug',
            'name' => 'Nome',
            'entity_type' => 'Tipo de Entidade',
            'threshold_field' => 'Campo de Limiar',
            'threshold_value' => 'Valor do Limiar',
            'min_events' => 'Eventos Mínimos',
            'max_days' => 'Dias Máximos',
            'score_base' => 'Pontuação Base',
            'score_increments' => 'Incrementos de Pontuação',
            'severity' => 'Severidade',
            'description_template' => 'Template de Descrição',
            'interpretation_aml' => 'Interpretação AML',
            'extra_params' => 'Parâmetros Extra',
            'is_active' => 'Ativo',
            'products' => 'Produtos',
            'products.*.product_name' => 'Nome do Produto',
            'products.*.type' => 'Tipo do Produto',
        ];
    }
}
