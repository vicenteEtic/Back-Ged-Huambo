<?php

namespace App\Http\Requests\RH\Benefit;

use App\Http\Requests\BaseFormRequest;

class EmployeeBenefitRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'benefit_type_id' => ['required', 'integer', 'exists:benefit_types,id'],
            'amount' => ['numeric', 'min:0'],
            'start_date' => ['required', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'status' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
