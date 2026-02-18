<?php

namespace App\Http\Requests\Entities;

use App\Http\Requests\BaseFormRequest;

class BeneficialRequest extends BaseFormRequest
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
            'risk_assessment_id' => 'required',
            'nationality' => 'required',
            'is_pep' => 'required',
            'is_sanctioned' => 'required',
            'processesReportedAuthoritie' => 'required'
        ];
    }
}