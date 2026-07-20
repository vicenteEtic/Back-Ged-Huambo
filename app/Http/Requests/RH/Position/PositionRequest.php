<?php

namespace App\Http\Requests\RH\Position;

use App\Http\Requests\BaseFormRequest;

class PositionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('position');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:positions,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'department_id' => [$this->requiredOnCreate(), 'integer', 'exists:departments,id'],
            'level' => ['integer', 'min:1'],
            'base_salary' => ['numeric', 'min:0'],
            'requirements' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
