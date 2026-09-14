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
        if ($this->has('date') && $this->input('date')) {
            $this->merge(['date' => Carbon::parse($this->input('date'))->format('Y-m-d')]);
        }
    }

    public function rules(): array
    {
        return [
            'date' => ['nullable', 'date'],
            'department_ids' => ['nullable', 'array'],
            'department_ids.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'department_ids.*.exists' => 'Departamento inválido na filtragem.',
        ];
    }
}