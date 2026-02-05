<?php

namespace App\Http\Requests\KYT;

use App\Http\Requests\BaseFormRequest;

class kytrulesRequest extends BaseFormRequest
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
        'code' => [
            'required',
            'string',
            'max:100',
            'regex:/^[A-Z0-9_]+$/',
            'unique:kyt_rules,code,' . $this->route('kyt_rule'),
        ],

        'name' => [
            'required',
            'string',
            'max:255',
        ],

        'severity' => [
            'required',
            'in:Alto,Médio,Baixo',
        ],

        'score' => [
            'required',
            'integer',
            'min:0',
            'max:100',
        ],

        'active' => [
            'required',
            'boolean',
        ],

        'parameters' => [
            'required',
            'array',
        ],

        // validações genéricas dos parâmetros
        'parameters.*' => [
            'nullable',
        ],
    ];
}

}