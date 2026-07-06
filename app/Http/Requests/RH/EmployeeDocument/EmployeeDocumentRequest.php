<?php

namespace App\Http\Requests\RH\EmployeeDocument;

use App\Http\Requests\BaseFormRequest;

class EmployeeDocumentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('document');
        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'document_type' => ['required', 'string', 'max:100'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_path' => ['required', 'array'],
            'file_path.*' => ['file', 'max:1048576'],
            'expiry_date' => ['nullable', 'date'],
            'is_verified' => ['boolean'],
        ];
    }
}
