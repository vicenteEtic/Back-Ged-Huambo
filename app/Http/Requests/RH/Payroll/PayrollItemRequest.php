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

    public function messages(): array
    {
        return [
            'payroll_period_id.required' => 'O período de pagamento é obrigatório.',
            'payroll_period_id.integer' => 'O período de pagamento deve ser um número inteiro.',
            'payroll_period_id.exists' => 'O período de pagamento seleccionado não existe.',
            'employee_id.required' => 'O funcionário é obrigatório.',
            'employee_id.integer' => 'O funcionário deve ser um número inteiro.',
            'employee_id.exists' => 'O funcionário seleccionado não existe.',
            'base_salary.required' => 'O salário base é obrigatório.',
            'base_salary.numeric' => 'O salário base deve ser um número.',
            'base_salary.min' => 'O salário base deve ser igual ou superior a 0.',
        ];
    }
}
