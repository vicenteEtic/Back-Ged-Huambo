<?php

namespace App\Http\Requests\RH\Archive;

use App\Enum\DocumentConfidentiality;
use App\Enum\DocumentStatus;
use App\Http\Requests\BaseFormRequest;

class ArchiveDocumentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'category_id' => [$this->requiredOnCreate(), 'integer', 'exists:archive_categories,id'],
            'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'title' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'document_number' => ['nullable', 'string', 'max:100'],
            'reference_number' => ['nullable', 'string', 'max:100'],
            'issuing_authority' => ['nullable', 'string', 'max:255'],
            'file' => ['nullable', 'file', 'max:102400'],
            'file_path' => ['nullable', 'string', 'max:500'],
            'file_type' => ['nullable', 'string', 'max:50'],
            'file_size' => ['nullable', 'integer', 'min:0'],
            'mime_type' => ['nullable', 'string', 'max:100'],
            'status' => ['string', 'in:' . implode(',', DocumentStatus::values())],
            'confidentiality' => ['string', 'in:' . implode(',', DocumentConfidentiality::values())],
            'metadata' => ['nullable', 'json'],
            'issued_date' => ['nullable', 'date'],
            'expiry_date' => ['nullable', 'date', 'after_or_equal:issued_date'],
            'is_physical_copy' => ['boolean'],
            'physical_location' => ['nullable', 'string', 'max:255'],
            'tags' => ['nullable', 'json'],
        ];
    }
}
