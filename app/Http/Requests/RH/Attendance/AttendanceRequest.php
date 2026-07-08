<?php

namespace App\Http\Requests\RH\Attendance;

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
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['string', 'in:present,absent,late,justified_absence,holiday,day_off'],
            'absence_type' => ['nullable', 'string', 'max:100'],
            'absence_reason' => ['nullable', 'string'],
            'is_justified' => ['boolean'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
