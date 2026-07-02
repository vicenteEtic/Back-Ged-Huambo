<?php

namespace App\Http\Requests\RH\Benefit;

use App\Http\Requests\BaseFormRequest;

class BenefitClaimRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'benefit_type_id' => ['required', 'exists:benefit_types,id'],
            'amount_requested' => ['required', 'numeric', 'min:0'],
            'amount_approved' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'status' => ['string', 'max:30'],
            'requested_date' => ['nullable', 'date'],
            'approved_date' => ['nullable', 'date'],
            'approved_by' => ['nullable', 'exists:users,id'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
