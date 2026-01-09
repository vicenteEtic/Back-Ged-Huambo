<?php

namespace App\Http\Requests\Transation\CustomerProfiles;

use App\Http\Requests\BaseFormRequest;

class CustomerProfilesRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'entity_id' => 'required',
            'avg_transaction_amount' => 'required',
            'std_transaction_amount' => 'required',
            'avg_transactions_per_month' => 'required',
            'early_redemptions' => 'required'
        ];
    }
}