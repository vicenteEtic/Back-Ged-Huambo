<?php

namespace App\Http\Requests\RH\Category;

use App\Http\Requests\BaseFormRequest;

class CategoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', "unique:categories,code,{$id},id"],
            'group' => ['nullable', 'string', 'max:100'],
            'level' => ['nullable', 'integer', 'min:1'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'is_active' => ['boolean'],
        ];
    }
}
