<?php

namespace App\Http\Requests\RH\Payroll;

use App\Http\Requests\BaseFormRequest;

class IrtBracketRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'bracket' => [$this->requiredOnCreate(), 'integer', "unique:irt_brackets,bracket,{$id},id"],
            'min_salary' => [$this->requiredOnCreate(), 'numeric', 'min:0'],
            'max_salary' => [$this->requiredOnCreate(), 'numeric', 'min:0'],
            'fixed_amount' => ['nullable', 'numeric', 'min:0'],
            'rate' => ['nullable', 'numeric', 'min:0', 'max:1'],
            'excess_over' => ['nullable', 'numeric', 'min:0'],
            'is_exempt' => ['nullable', 'boolean'],
            'active' => ['nullable', 'boolean'],
        ];
    }
}
