<?php

namespace App\Http\Requests\RH\Career;

use App\Enum\ProgressionType;
use App\Http\Requests\BaseFormRequest;

class ProgressionRequestRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'rule_id' => ['nullable', 'integer', 'exists:progression_rules,id'],
            'type' => ['required_without:rule_id', 'nullable', 'string', 'in:' . implode(',', ProgressionType::values())],
            'to_category' => ['nullable', 'string', 'max:100'],
            'to_position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'new_salary' => ['nullable', 'numeric', 'min:0'],
            'justification' => ['nullable', 'string'],
            'effective_date' => ['nullable', 'date'],
        ];
    }
}
