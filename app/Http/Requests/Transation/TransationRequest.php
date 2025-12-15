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
        '*.entite_id' => 'required',
        '*.amount'    => 'required|numeric',
        '*.currency'  => 'required|string',
        '*.date'      => 'required|date',
        '*.type'      => 'required|string',
        '*.status'    => 'required|string',
        '*.channel'   => 'required|string',
        '*.description' => 'nullable|string',
        '*.category'    => 'nullable|string',
        '*.risk_score'  => 'nullable|numeric',
        '*.ip_address'  => 'nullable|ip',
        '*.device'      => 'nullable|string',
        '*.notes'       => 'nullable|string',
    ];
}

}