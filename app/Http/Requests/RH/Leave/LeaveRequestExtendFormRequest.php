<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;

class LeaveRequestExtendFormRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
