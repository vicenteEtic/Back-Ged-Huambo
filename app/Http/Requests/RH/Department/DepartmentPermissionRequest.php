<?php

namespace App\Http\Requests\RH\Department;

use App\Http\Requests\BaseFormRequest;

class DepartmentPermissionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => [$this->requiredOnCreate(), 'integer', 'exists:departments,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
            'permission_id' => [$this->requiredOnCreate(), 'integer', 'exists:permission,id'],
            'is_active' => ['boolean'],
        ];
    }
}
