<?php

namespace App\Http\Requests\RH\Career;

use App\Enum\ProgressionType;
use App\Http\Requests\BaseFormRequest;

class ProgressionRuleRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:progression_rules,code,{$id},id"],
            'type' => [$this->requiredOnCreate(), 'string', 'in:' . implode(',', ProgressionType::values())],
            'description' => ['nullable', 'string'],
            'min_months_in_category' => ['integer', 'min:0'],
            'min_performance_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'requires_training' => ['boolean'],
            'requires_evaluation' => ['boolean'],
            'from_category' => ['nullable', 'string', 'max:100'],
            'to_category' => ['nullable', 'string', 'max:100'],
            'from_level' => ['nullable', 'integer', 'min:1'],
            'to_level' => ['nullable', 'integer', 'min:1'],
            'salary_increase_percent' => ['numeric', 'min:0', 'max:100'],
            'is_active' => ['boolean'],
        ];
    }
}
