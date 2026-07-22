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
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'benefit_type_id' => [$this->requiredOnCreate(), 'integer', 'exists:benefit_types,id'],
            'amount_requested' => [$this->requiredOnCreate(), 'numeric', 'min:0'],
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
