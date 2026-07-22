<?php

namespace App\Http\Requests\RH\Payroll;

use App\Http\Requests\BaseFormRequest;

class PayrollItemRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'payroll_period_id' => [$this->requiredOnCreate(), 'integer', 'exists:payroll_periods,id'],
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'base_salary' => [$this->requiredOnCreate(), 'numeric', 'min:0'],
            'transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'overtime' => ['nullable', 'numeric', 'min:0'],
            'other_earnings' => ['nullable', 'numeric', 'min:0'],
            'other_deductions' => ['nullable', 'numeric', 'min:0'],
            'status' => ['nullable', 'string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
