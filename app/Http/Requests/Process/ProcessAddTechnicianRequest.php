<?php

namespace App\Http\Requests\Process;

use App\Http\Requests\BaseFormRequest;

class ProcessAddTechnicianRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'visibility' => ['nullable', 'string', 'in:public,private'],
        ];
    }
}
