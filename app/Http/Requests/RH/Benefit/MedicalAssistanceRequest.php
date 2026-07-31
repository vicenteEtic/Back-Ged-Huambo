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
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'assistance_type' => [$this->requiredOnCreate(), 'string', 'max:100'],
            'provider' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['numeric', 'min:0'],
            'assistance_date' => [$this->requiredOnCreate(), 'date'],
            'status' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
