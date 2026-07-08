<?php

namespace App\Http\Requests\RH\Career;

use App\Http\Requests\BaseFormRequest;

class RetirementProcessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'request_date' => ['required', 'date'],
            'effective_date' => ['nullable', 'date'],
            'status' => ['string', 'max:30'],
            'final_salary' => ['numeric', 'min:0'],
            'pension_amount' => ['numeric', 'min:0'],
            'pension_type' => ['nullable', 'string', 'max:100'],
            'documents' => ['nullable', 'string'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'approved_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
