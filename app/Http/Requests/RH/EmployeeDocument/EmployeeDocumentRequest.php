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
            'document_type' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_path' => ['required', 'array'],
            'file_path.*' => ['file', 'max:1048576'],
            'expiry_date' => ['nullable', 'date'],
            'is_verified' => ['boolean'],
        ];
    }
}
