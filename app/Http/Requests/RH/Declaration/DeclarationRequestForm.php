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

        $rules += $this->declarationFieldRules();

        if ($id) {
            $rules['status'] = ['string', 'max:30'];
            $rules['issued_number'] = ['nullable', 'string', 'max:50'];
            $rules['notes'] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function declarationFieldRules(): array
    {
        $text = ['nullable', 'string', 'max:255'];

        return [
            'full_name' => $text,
            'gender' => ['nullable', 'string', 'in:masculino,feminino'],
            'issue_date' => ['nullable', 'date'],
            'declaration_number' => ['nullable', 'string', 'max:50'],
            'signer_name' => $text,
            'signer_role' => $text,
            'position_category' => $text,
            'workplace' => $text,
            'employment_bond' => $text,
            'bank' => ['nullable', 'string', 'max:100'],
            'salary_type' => ['nullable', 'string', 'in:base,liquido,base_e_liquido'],
            'salary_amount' => ['nullable', 'numeric', 'min:0'],
            'salary_words' => ['nullable', 'string', 'max:500'],
            'net_salary_amount' => ['nullable', 'numeric', 'min:0'],
            'net_salary_words' => ['nullable', 'string', 'max:500'],
            'salutation' => ['nullable', 'string', 'max:100'],
            'position' => $text,
            'service_time' => $text,
            'admission_label' => ['nullable', 'string', 'max:50'],
            'admission_date' => ['nullable', 'date'],
            'account_number' => ['nullable', 'string', 'max:50'],
            'consignment_account' => ['nullable', 'string', 'max:50'],
            'id_card_number' => ['nullable', 'string', 'max:50'],
            'agent_number' => ['nullable', 'string', 'max:50'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'domicile_branch' => $text,
            'credit_purpose' => $text,
            'employer_entity' => $text,
            'paying_entity' => $text,
            'payment_day' => $text,
            'embassy' => $text,
            'embassy_city' => $text,
            'residence' => $text,
            'correction_type' => ['nullable', 'string', 'in:correccao,acrescimo'],
            'issuing_department' => $text,
        ];
    }
}
