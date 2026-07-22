<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'process_type' => ['required', 'string'],
            'subject' => ['required', 'string', 'max:255'],
            'reception_date' => ['required', 'date'],
            'reception_time' => ['required', 'date_format:H:i'],
          'reference_number' => [
            'nullable',
            'string',
            'max:100',
            'unique:processes,reference_number',
        ],
            'document_date' => ['nullable', 'date'],
            'notes' => ['nullable', 'string'],
            'sender_entity' => ['nullable', 'string', 'max:255'],
            'file_path' => ['nullable', 'array'],
            'file_path.*' => ['file', 'max:10485760'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'justification' => ['nullable', 'string'],
            'classification' => ['nullable', 'string', 'in:pedido,reclamacao,sugestao,informacao,outro'],
            'deadline' => ['nullable', 'date'],
            'origin_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'origin_area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'target_department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
        ];
    }
}
