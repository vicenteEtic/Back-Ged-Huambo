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
            'type' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['nullable', 'integer', 'exists:employees,id'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'is_active' => ['boolean'],
        ];
    }
}
