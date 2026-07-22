<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;

class LeaveTypeRequest extends BaseFormRequest
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
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:leave_types,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'default_days' => ['integer', 'min:0'],
            'allows_carryover' => ['boolean'],
            'max_carryover_days' => ['integer', 'min:0'],
            'requires_attachment' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
