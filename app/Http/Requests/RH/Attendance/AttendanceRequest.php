<?php

namespace App\Http\Requests\RH\Attendance;

use App\Enum\AttendanceStatus;
use App\Http\Requests\BaseFormRequest;
use App\Support\TimeNormalizer;

class AttendanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->has('check_in')) {
            $this->merge(['check_in' => TimeNormalizer::normalize($this->input('check_in'))]);
        }
        if ($this->has('check_out')) {
            $this->merge(['check_out' => TimeNormalizer::normalize($this->input('check_out'))]);
        }
    }

    public function rules(): array
    {
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'date' => [$this->requiredOnCreate(), 'date'],
            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
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
