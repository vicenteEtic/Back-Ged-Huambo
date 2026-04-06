<?php

namespace App\Http\Requests\Entities;

use App\Http\Requests\BaseFormRequest;

class BeneficialOwnerRequest extends BaseFormRequest
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
            'pep' => 'required',
            'santion'=> 'required',
            'risk_assessment_id' => 'required',
            'nationality' => 'string',
            'percentage' => 'string',
            'is_legal_representative' => 'string',



        ];
    }
}
