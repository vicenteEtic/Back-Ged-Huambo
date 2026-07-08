<?php

namespace App\Http\Requests\RH\Performance;

use App\Http\Requests\BaseFormRequest;

class PerformanceEvaluationRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'evaluator_id' => ['required', 'integer', 'exists:users,id'],
            'cycle_id' => ['required', 'integer', 'exists:performance_cycles,id'],
            'overall_score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'strengths' => ['nullable', 'string'],
            'improvements' => ['nullable', 'string'],
            'notes' => ['nullable', 'string'],
            'status' => ['string', 'max:30'],
            'submitted_at' => ['nullable', 'date'],
        ];
    }
}
