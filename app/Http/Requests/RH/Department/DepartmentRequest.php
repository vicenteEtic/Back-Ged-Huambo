<?php

namespace App\Http\Requests\RH\Department;

use App\Http\Requests\BaseFormRequest;

class DepartmentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'type' => ['nullable', 'string', 'in:expediente,gabinete,departamento,vice_governador'],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_active' => ['boolean'],
        ];
    }
}
