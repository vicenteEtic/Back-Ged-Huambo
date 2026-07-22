<?php

namespace App\Http\Requests\RH\Payroll;

use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

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
            'employee_id' => [
                $this->requiredOnCreate(), 'integer', 'exists:employees,id',
                Rule::unique('payroll_items')->where(fn ($query) => $query
                    ->where('payroll_period_id', $this->input('payroll_period_id'))
                )->ignore($id),
            ],
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
            'employee_id.unique' => 'Este funcionário já possui um item de vencimento para o período seleccionado.',
        ];
    }
}
