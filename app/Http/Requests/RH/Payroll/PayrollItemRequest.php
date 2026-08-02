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
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            return [
                'employee_id' => ['nullable', 'integer', 'exists:employees,id'],
                'base_salary' => ['nullable', 'numeric', 'min:0'],
                'transport_allowance' => ['nullable', 'numeric', 'min:0'],
                'meal_allowance' => ['nullable', 'numeric', 'min:0'],
                'overtime' => ['nullable', 'numeric', 'min:0'],
                'other_earnings' => ['nullable', 'numeric', 'min:0'],
                'other_deductions' => ['nullable', 'numeric', 'min:0'],
                'status' => ['nullable', 'string', 'max:30'],
                'notes' => ['nullable', 'string'],
            ];
        }

        return [
            'payroll_period_id' => ['required', 'integer', 'exists:payroll_periods,id'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.employee_id' => ['required', 'integer', 'exists:employees,id'],
            'items.*.base_salary' => ['required', 'numeric', 'min:0'],
            'items.*.transport_allowance' => ['nullable', 'numeric', 'min:0'],
            'items.*.meal_allowance' => ['nullable', 'numeric', 'min:0'],
            'items.*.overtime' => ['nullable', 'numeric', 'min:0'],
            'items.*.other_earnings' => ['nullable', 'numeric', 'min:0'],
            'items.*.other_deductions' => ['nullable', 'numeric', 'min:0'],
            'items.*.status' => ['nullable', 'string', 'max:30'],
            'items.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'payroll_period_id.required' => 'O período de pagamento é obrigatório.',
            'payroll_period_id.integer' => 'O período de pagamento deve ser um número inteiro.',
            'payroll_period_id.exists' => 'O período de pagamento seleccionado não existe.',
            'items.required' => 'Envie pelo menos um funcionário.',
            'items.array' => 'Os funcionários devem ser enviados como array.',
            'items.min' => 'Envie pelo menos um funcionário.',
            'items.*.employee_id.required' => 'O funcionário é obrigatório.',
            'items.*.employee_id.integer' => 'O funcionário deve ser um número inteiro.',
            'items.*.employee_id.exists' => 'Um dos funcionários seleccionados não existe.',
            'items.*.base_salary.required' => 'O salário base é obrigatório.',
            'items.*.base_salary.numeric' => 'O salário base deve ser um número.',
            'items.*.base_salary.min' => 'O salário base deve ser igual ou superior a 0.',
            'employee_id.exists' => 'O funcionário seleccionado não existe.',
            'base_salary.numeric' => 'O salário base deve ser um número.',
            'base_salary.min' => 'O salário base deve ser igual ou superior a 0.',
        ];
    }
}
