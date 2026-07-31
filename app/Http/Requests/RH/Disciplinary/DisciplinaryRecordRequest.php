<?php

namespace App\Http\Requests\RH\Disciplinary;

use App\Http\Requests\BaseFormRequest;

class DisciplinaryRecordRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }

    public function messages(): array
    {
        return [
            'occurred_at.before_or_equal' => 'A data da ocorrência deve ser uma data anterior ou igual a :date.',
            'occurred_at.date' => 'A data da ocorrência deve ser uma data válida.',
            'sanction_start.before_or_equal' => 'A data de início da sanção deve ser uma data anterior ou igual a :date.',
            'sanction_start.date' => 'A data de início da sanção deve ser uma data válida.',
            'sanction_end.after_or_equal' => 'A data de término da sanção deve ser igual ou posterior à data de início.',
        ];
    }

    public function rules(): array
    {
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'disciplinary_type_id' => [$this->requiredOnCreate(), 'integer', 'exists:disciplinary_types,id'],
            'occurred_at' => [$this->requiredOnCreate(), 'date', 'before_or_equal:today'],
            'description' => [$this->requiredOnCreate(), 'string'],
            'evidence_path' => ['nullable', 'file', 'max:10240'],
            'status' => ['string', 'max:30'],
            'reported_by' => ['nullable', 'exists:users,id'],
            'resolution' => ['nullable', 'string'],
            'sanction' => ['nullable', 'string', 'max:255'],
            'sanction_start' => ['nullable', 'date', 'before_or_equal:today'],
            'sanction_end' => ['nullable', 'date', 'after_or_equal:sanction_start'],
        ];
    }
}
