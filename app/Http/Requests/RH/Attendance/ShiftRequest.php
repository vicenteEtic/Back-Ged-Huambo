<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;

class ShiftRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('shift');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:shifts,code,{$id},id"],
            'start_time' => [$this->requiredOnCreate(), 'date_format:H:i:s'],
            'end_time' => [$this->requiredOnCreate(), 'date_format:H:i:s', 'after:start_time'],
            'grace_minutes' => ['integer', 'min:0', 'max:120'],
            'duration_hours' => ['numeric', 'min:0.5', 'max:24'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
