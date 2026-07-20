<?php

namespace App\Http\Requests\RH\Benefit;

use App\Enum\BenefitCategory;
use App\Http\Requests\BaseFormRequest;

class BenefitTypeRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('benefit_type');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:benefit_types,code,{$id},id"],
            'category' => ['nullable', 'string', 'in:' . implode(',', BenefitCategory::values())],
            'description' => ['nullable', 'string'],
            'provider' => ['nullable', 'string', 'max:255'],
            'default_amount' => ['numeric', 'min:0'],
            'frequency' => ['string', 'max:50'],
            'is_active' => ['boolean'],
        ];
    }
}
