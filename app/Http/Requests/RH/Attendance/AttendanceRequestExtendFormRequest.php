<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;

class AttendanceRequestExtendFormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
            'description' => ['nullable', 'string', 'max:2000'],
            'oversight_note' => ['nullable', 'string', 'max:1000'],

            'documents' => ['nullable', 'array'],
            'documents.*.type' => ['nullable', 'string'],
            'documents.*.file' => ['nullable', 'file'],
        ];
    }

    public function messages(): array
    {
        return [];
    }
}
