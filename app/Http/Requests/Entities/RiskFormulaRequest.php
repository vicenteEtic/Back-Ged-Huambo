<?php

namespace App\Http\Requests\Entities;

use App\Http\Requests\BaseFormRequest;

class RiskFormulaRequest extends BaseFormRequest
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
            'name' => 'required',
            'identification_capacity' => 'required',
            // 'form_establishment' => 'required',
            'category' => 'nullable',
            'status_residence' => 'required',
            'profession' => 'required',
            'pep' => 'required',
            'country_residence' => 'required',
            'nationality' => 'required',
            'entity_type' => 'required',
            'channel' => 'required',
            'product_risk' => 'required',
            'santion' => 'required',
            'distributionChannel' => 'required'
        ];
    }
}