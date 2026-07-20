<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;

class LeavePlanRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'year' => [$this->requiredOnCreate(), 'integer', 'min:2000', 'max:2100'],
            'total_days_entitled' => [$this->requiredOnCreate(), 'numeric', 'min:0', 'max:365'],
            'days_used' => ['numeric', 'min:0'],
            'days_pending' => ['numeric', 'min:0'],
            'observations' => ['nullable', 'string'],
        ];
    }
}
