<?php

namespace App\Http\Requests\Alert\GrupoAlertEmails;

use App\Http\Requests\BaseFormRequest;

class GrupoAlertEmailsRequest extends BaseFormRequest
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
            'name' => ['required', 'string', 'max:255'],
             'description'=> 'nullable',
            'grupo_Type'   => ['required', 'array'],   
            'grupo_Type.*' => ['required', 'string'],    // valida cada item do array
        ];
    }
    
}