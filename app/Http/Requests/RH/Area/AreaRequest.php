<?php

namespace App\Http\Requests\RH\Area;

use App\Http\Requests\BaseFormRequest;

class AreaRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'department_id' => [$this->requiredOnCreate(), 'integer', 'exists:departments,id'],
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:areas,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'responsible_id' => ['nullable', 'integer', 'exists:users,id'],
            'is_active' => ['boolean'],
        ];
    }
}
