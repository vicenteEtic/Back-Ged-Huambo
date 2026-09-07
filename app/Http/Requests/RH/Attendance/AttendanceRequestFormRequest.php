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
                'nullable',
                'string',
                'required_without:attendance_request_type_id',
            ],

            'attendance_request_type_id' => [
                'nullable',
                'integer',
                'exists:attendance_request_types,id',
                'required_without:type_code',
            ],

            'start_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => [$this->requiredOnCreate(), 'date', 'after_or_equal:start_date'],

            'applies_full_day' => ['nullable', 'boolean'],
            'reason' => [$this->requiredOnCreate(), 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'oversight_note' => ['nullable', 'string', 'max:1000'],

            'benefit_start_date' => ['nullable', 'date', 'before:today'],

            'documents' => ['nullable', 'array'],
            'documents.*.type' => ['nullable', 'string'],
            'documents.*.file' => ['nullable', 'file'],
        ];
    }

    public function messages(): array
    {
        return [
            'end_date.after_or_equal' => 'A data final não pode ser anterior à data inicial.',
            'type_code.in' => 'Tipo de solicitação inválido.',
            'type_code.required_without' => 'Indique o tipo de dispensa (id ou código).',
            'attendance_request_type_id.required_without' => 'Indique o tipo de dispensa (id ou código).',
            'attendance_request_type_id.exists' => 'O tipo de dispensa indicado não existe.',
            'benefit_start_date.before' => 'A data de nascimento da criança deve ser anterior a hoje.',
        ];
    }
}
