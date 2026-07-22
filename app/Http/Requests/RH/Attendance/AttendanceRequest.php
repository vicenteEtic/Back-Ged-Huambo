<?php

namespace App\Http\Requests\RH\Attendance;

use App\Enum\AttendanceStatus;
use App\Http\Requests\BaseFormRequest;

class AttendanceRequest extends BaseFormRequest
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
            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['string', 'in:' . implode(',', AttendanceStatus::values())],
            'absence_type' => ['nullable', 'string', 'max:100'],
            'absence_reason' => ['nullable', 'string'],
            'is_justified' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
