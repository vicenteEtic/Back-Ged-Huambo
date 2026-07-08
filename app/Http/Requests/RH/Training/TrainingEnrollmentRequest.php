<?php

namespace App\Http\Requests\RH\Training;

use App\Http\Requests\BaseFormRequest;

class TrainingEnrollmentRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'session_id' => ['required', 'integer', 'exists:training_sessions,id'],
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'status' => ['string', 'max:30'],
            'grade' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
