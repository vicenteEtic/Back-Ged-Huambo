<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessDispatchRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'department_id' => ['required', 'integer', 'exists:departments,id'],
            'area_id' => ['nullable', 'integer', 'exists:areas,id'],
        ];
    }
}
