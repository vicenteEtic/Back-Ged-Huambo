<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;
use App\Support\Dispensa;
use Illuminate\Validation\Rule;

class AttendanceRequestFormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],

            'type_code' => [
                $this->requiredOnCreate(),
                Rule::in(array_column(Dispensa::typeRegistry(), 'code')),
            ],

            'start_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => [$this->requiredOnCreate(), 'date', 'after_or_equal:start_date'],

            'applies_full_day' => ['nullable', 'boolean'],
            'reason' => [$this->requiredOnCreate(), 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'oversight_note' => ['nullable', 'string', 'max:1000'],

            'benefit_start_date' => ['nullable', 'date', 'before:today'],

            'documents' => ['nullable', 'array', 'min:1'],
            'documents.*.type' => ['nullable', 'string'],
            'documents.*.file' => ['nullable', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'A data final não pode ser anterior à data inicial.',
            'type_code.in' => 'Tipo de solicitação inválido.',
            'benefit_start_date.before' => 'A data de nascimento da criança deve ser anterior a hoje.',
        ];
    }
}
