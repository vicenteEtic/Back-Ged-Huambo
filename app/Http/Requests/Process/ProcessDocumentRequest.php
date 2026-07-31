<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessDocumentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'document_type' => ['nullable', 'string', 'max:100'],
            'name' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'file_path' => ['required', 'array'],
            'file_path.*' => ['file', 'max:10485760'],
        ];
    }
}
