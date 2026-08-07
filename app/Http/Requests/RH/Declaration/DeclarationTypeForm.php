<?php

namespace App\Http\Requests\RH\Declaration;

use App\Enum\DeclarationTypeEnum;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;

class DeclarationTypeForm extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $allowedCodes = DeclarationTypeEnum::values();

        return [
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', Rule::in($allowedCodes), "unique:declaration_types,code,{$id},id"],
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'requires_approval' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
