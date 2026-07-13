<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessSubmitRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'notes' => ['nullable', 'string'],
            'file_path' => ['nullable', 'string'],
            'file_type' => ['nullable', 'string'],
            'file_size' => ['nullable', 'integer'],
            'mime_type' => ['nullable', 'string'],
        ];
    }
}
