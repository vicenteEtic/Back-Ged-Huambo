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
            'base_salary' => ['numeric', 'min:0'],
            'transport_allowance' => ['numeric', 'min:0'],
            'meal_allowance' => ['numeric', 'min:0'],
            'overtime' => ['numeric', 'min:0'],
            'other_earnings' => ['numeric', 'min:0'],
            'inss_deduction' => ['numeric', 'min:0'],
            'irt_deduction' => ['numeric', 'min:0'],
            'other_deductions' => ['numeric', 'min:0'],
            'gross_pay' => ['numeric', 'min:0'],
            'total_deductions' => ['numeric', 'min:0'],
            'net_pay' => ['numeric', 'min:0'],
            'status' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
