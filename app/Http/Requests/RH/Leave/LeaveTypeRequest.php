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
        $id = $this->route('leave_type');
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['required', 'string', 'max:50', "unique:leave_types,code,{$id},id"],
            'description' => ['nullable', 'string'],
            'default_days' => ['integer', 'min:0'],
            'allows_carryover' => ['boolean'],
            'max_carryover_days' => ['integer', 'min:0'],
            'requires_attachment' => ['boolean'],
            'is_active' => ['boolean'],
        ];
    }
}
