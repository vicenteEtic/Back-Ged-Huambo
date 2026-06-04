<?php

namespace App\Http\Requests\Entities;

use App\Http\Requests\BaseFormRequest;

class ImportDataRequest extends BaseFormRequest
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
            '*.identification_capacity' => ['nullable'],
            '*.beneficial_owners'     => ['nullable'],
            '*.category'             => ['nullable'],
            '*.channel'              => ['nullable'],
            '*.country_residence'    => ['nullable'],
            '*.customer_number'      => ['nullable'],
            '*.entity_type'          => ['nullable'],
            // '*.form_establishment'   => ['nullable'],
            '*.nationality'          => ['nullable'],
            '*.pep'                  => ['nullable'],
            '*.policy_number'        => ['nullable'],
            '*.product_risk'         => ['nullable'],
            '*.profession'           => ['nullable'],
            '*.social_denomination'  => ['nullable'],
            '*.status_residence'     => ['nullable'],
            'nif' =>  ['nullable'],
        ];
    }
}
