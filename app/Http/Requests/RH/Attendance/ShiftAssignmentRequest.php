<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;

class ShiftAssignmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'shift_id' => [$this->requiredOnCreate(), 'integer', 'exists:shifts,id'],
            'effective_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:effective_date'],
        ];
    }
}
