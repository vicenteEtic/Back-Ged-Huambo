<?php

namespace App\Http\Requests\RH\Disciplinary;

use App\Http\Requests\BaseFormRequest;

class DisciplinaryTypeRequest extends BaseFormRequest
{
    public function authorize(): bool { return true; }
    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'severity' => ['string', 'max:30'],
            'is_active' => ['boolean'],
        ];
    }
}
