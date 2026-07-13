<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessDispatchAreasRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'assignments' => ['required', 'array', 'min:1'],
            'assignments.*.department_id' => ['required', 'integer', 'exists:departments,id'],
            'assignments.*.area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'assignments.*.visibility' => ['nullable', 'string', 'in:public,private'],
            'assignments.*.technicians' => ['required', 'array', 'min:1'],
            'assignments.*.technicians.*' => ['integer', 'exists:users,id'],
            'assignments.*.priority' => ['nullable', 'string', 'in:low,normal,high,urgent'],
            'assignments.*.deadline' => ['nullable', 'date'],
            'assignments.*.notes' => ['nullable', 'string'],
        ];
    }
}
