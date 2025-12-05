<?php

namespace App\Http\Requests\Transation;

use App\Http\Requests\BaseFormRequest;

class TransationRequest extends BaseFormRequest
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
            'entite_id' => 'required',
            'amount' => 'required',
            'currency' => 'required',
            'date' => 'required',
            'type' => 'required',
            'status' => 'required',
            'channel' => 'required',
            'description' => 'required',
            'category' => 'required',
            'risk_score' => 'required',
            'ip_address' => 'required',
            'device' => 'required',
            'notes' => 'required'
        ];
    }
}