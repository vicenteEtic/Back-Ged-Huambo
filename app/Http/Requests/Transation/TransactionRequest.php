<?php

namespace App\Http\Requests\Transation;

use App\Http\Requests\BaseFormRequest;

class TransactionRequest extends BaseFormRequest
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
            'amount' => 'required',
            'type' => 'required',
            'transaction_date' => 'required'
        ];
    }
}