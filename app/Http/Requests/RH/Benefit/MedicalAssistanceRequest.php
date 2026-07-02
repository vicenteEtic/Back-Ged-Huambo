<?php

namespace App\Http\Requests\RH\Benefit;

use App\Http\Requests\BaseFormRequest;

class MedicalAssistanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'assistance_type' => ['required', 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['numeric', 'min:0'],
            'assistance_date' => ['required', 'date'],
            'status' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
