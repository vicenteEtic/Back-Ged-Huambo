<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;

class AbsenceJustificationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'date' => [$this->requiredOnCreate(), 'date'],
            'absence_type' => ['nullable', 'string', 'max:100'],
            'reason' => [$this->requiredOnCreate(), 'string'],
            'proof' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'status' => ['nullable', 'string', 'in:pending,approved,rejected'],
            'review_notes' => ['nullable', 'string'],
        ];
    }
}
