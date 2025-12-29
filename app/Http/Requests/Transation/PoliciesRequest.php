<?php

namespace App\Http\Requests\Transation;

use App\Http\Requests\BaseFormRequest;

class PoliciesRequest extends BaseFormRequest
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
            'contract_number' => 'required',
            'product' => 'required',
            'channel' => 'required',
            'agent' => 'required',
            'start_date' => 'required',
            'end_date' => 'required',
            'issue_date' => 'required',
            'renewal_date' => 'required',
            'capital' => 'required',
            'premium_simple' => 'required',
            'premium_total' => 'required',
            'charges' => 'required',
            'interest' => 'required',
            'status' => 'required'
        ];
    }
}