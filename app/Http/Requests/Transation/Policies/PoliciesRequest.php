<?php

namespace App\Http\Requests\Transation\Policies;

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
        'records' => 'required|array|min:1',

        'records.*.policy_number'   => 'required',
        'records.*.customer_number' => 'required',
        'records.*.nif'             => 'required',
        'records.*.contract_number' => 'required',
        'records.*.product'         => 'required',
        'records.*.channel'         => 'required',
        'records.*.agent'           => 'required',
        'records.*.start_date'      => 'required|date',
        'records.*.end_date'        => 'required|date',
        'records.*.issue_date'      => 'required|date',
        'records.*.renewal_date'    => 'required|date',
        'records.*.capital'         => 'required|numeric',
        'records.*.premium_simple'  => 'required|numeric',
        'records.*.premium_total'   => 'required|numeric',
        'records.*.charges'         => 'required|numeric',
        'records.*.interest'        => 'required|numeric',
        'records.*.status'          => 'required',
    ];
}



}
