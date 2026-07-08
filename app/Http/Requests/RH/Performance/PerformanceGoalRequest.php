<?php

namespace App\Http\Requests\RH\Performance;

use App\Http\Requests\BaseFormRequest;

class PerformanceGoalRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'cycle_id' => ['required', 'integer', 'exists:performance_cycles,id'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'category' => ['nullable', 'string', 'max:100'],
            'weight' => ['numeric', 'min:0', 'max:100'],
            'score' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
