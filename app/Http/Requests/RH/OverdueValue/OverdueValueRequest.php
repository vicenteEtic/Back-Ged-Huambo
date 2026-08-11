<?php

namespace App\Http\Requests\RH\OverdueValue;

use App\Enum\OverdueValueStatus;
use App\Enum\OverdueValueType;
use App\Http\Requests\BaseFormRequest;

class OverdueValueRequest extends BaseFormRequest
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
            'type' => [$this->requiredOnCreate(), 'string', 'in:'.implode(',', OverdueValueType::values())],
            'description' => [$this->requiredOnCreate(), 'string', 'max:500'],
            'amount' => [$this->requiredOnCreate(), 'numeric', 'min:0.01'],
            'paid_amount' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'in:'.implode(',', OverdueValueStatus::values())],
            'due_date' => ['nullable', 'date'],
            'settled_date' => ['nullable', 'date'],
            'reference_number' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'recorded_by' => ['nullable', 'exists:users,id'],
        ];
    }
}
