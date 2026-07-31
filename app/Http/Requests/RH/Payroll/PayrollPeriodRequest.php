<?php

namespace App\Http\Requests\RH\Payroll;

use App\Http\Requests\BaseFormRequest;

class PayrollPeriodRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'code' => [$this->requiredOnCreate(), 'string', 'max:20', "unique:payroll_periods,code,{$id},id"],
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'start_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => [$this->requiredOnCreate(), 'date', 'after_or_equal:start_date'],
            'payment_date' => ['nullable', 'date'],
            'status' => ['string', 'max:30'],
            'notes' => ['nullable', 'string'],
        ];
    }
}
