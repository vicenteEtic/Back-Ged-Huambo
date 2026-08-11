<?php

namespace App\Http\Requests\RH\EmployeeDocument;

use App\Http\Requests\BaseFormRequest;

class DocumentTypeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:document_types,code,{$id},id"],
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'has_number' => ['boolean'],
            'has_issue_date' => ['boolean'],
            'has_expiry_date' => ['boolean'],
            'has_place_of_issue' => ['boolean'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
