<?php

namespace App\Http\Requests\RH\Performance;

use App\Http\Requests\BaseFormRequest;

class EvaluationCriterionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('evaluation_criterion');
        return [
            'cycle_id' => ['required', 'exists:performance_cycles,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'section' => ['nullable', 'string', 'max:100'],
            'weight' => ['numeric', 'min:0', 'max:100'],
            'max_score' => ['integer', 'min:1', 'max:1000'],
            'is_active' => ['boolean'],
        ];
    }
}
