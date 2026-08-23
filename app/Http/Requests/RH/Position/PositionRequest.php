<?php

namespace App\Http\Requests\RH\Position;

use App\Http\Requests\BaseFormRequest;

class PositionRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
        ];
    }
}
