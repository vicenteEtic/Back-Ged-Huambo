<?php

namespace App\Http\Requests\RH\Archive;

use App\Enum\DocumentSharePermission;
use App\Http\Requests\BaseFormRequest;

class ArchiveDocumentShareRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'shared_with_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'shared_with_employee_id' => ['nullable', 'integer', 'exists:employees,id'],
            'permission' => ['string', 'in:' . implode(',', DocumentSharePermission::values())],
            'expires_at' => ['nullable', 'date', 'after:now'],
        ];
    }
}
