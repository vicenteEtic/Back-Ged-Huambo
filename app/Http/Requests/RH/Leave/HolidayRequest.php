<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;

class HolidayRequest extends BaseFormRequest
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
            'date' => [$this->requiredOnCreate(), 'date', "unique:holidays,date,{$id},id"],
            'recurrent' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
