<?php

namespace App\Http\Requests\RH\Performance;

use App\Http\Requests\BaseFormRequest;

class EvaluationScoreRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'evaluation_id' => [$this->requiredOnCreate(), 'integer', 'exists:performance_evaluations,id'],
            'criterion_id' => [$this->requiredOnCreate(), 'integer', 'exists:evaluation_criteria,id'],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comment' => ['nullable', 'string'],
        ];
    }
}
