<?php

namespace App\Http\Requests\RH\Declaration;

use App\Http\Requests\BaseFormRequest;

class DeclarationRequestForm extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $rules = [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'declaration_type_id' => [$this->requiredOnCreate(), 'integer', 'exists:declaration_types,id'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'institution_type' => ['nullable', 'string', 'max:100'],
            'purpose' => ['nullable', 'string'],
            'additional_info' => ['nullable', 'string'],
        ];

        if ($id) {
            $rules['status'] = ['string', 'max:30'];
            $rules['issued_number'] = ['nullable', 'string', 'max:50'];
            $rules['notes'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
