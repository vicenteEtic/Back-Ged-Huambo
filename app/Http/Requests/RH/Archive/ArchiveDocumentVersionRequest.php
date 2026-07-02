<?php

namespace App\Http\Requests\RH\Archive;

use App\Http\Requests\BaseFormRequest;

class ArchiveDocumentVersionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'version_number' => ['required', 'integer', 'min:1'],
            'file_path' => ['required', 'string', 'max:500'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
