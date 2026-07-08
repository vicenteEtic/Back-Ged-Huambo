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
        $id = $this->route('department');
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', "unique:departments,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'parent_id' => ['nullable', 'integer', 'exists:departments,id'],
            'is_active' => ['boolean'],
        ];
    }
}
