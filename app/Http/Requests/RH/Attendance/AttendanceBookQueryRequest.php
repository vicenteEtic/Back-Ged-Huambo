<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;
use Carbon\Carbon;

class AttendanceBookQueryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        foreach (['date', 'start_date', 'end_date'] as $field) {
            if ($this->has($field) && $this->input($field)) {
                $this->merge([$field => Carbon::parse($this->input($field))->format('Y-m-d')]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'start_date' => ['nullable', 'date', 'required_with:end_date'],
            'end_date' => ['nullable', 'date', 'required_with:start_date'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_ids.*.exists' => 'Departamento inválido na filtragem.',
            'start_date.required_with' => 'Para consultar um intervalo, envie também a data final (end_date).',
            'end_date.required_with' => 'Para consultar um intervalo, envie também a data inicial (start_date).',
        ];
    }
}