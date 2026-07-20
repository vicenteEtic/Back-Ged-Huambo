<?php

namespace App\Http\Requests\RH\FunctionalHistory;

use App\Enum\FunctionalHistoryType;
use App\Http\Requests\BaseFormRequest;

class FunctionalHistoryRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'type' => [$this->requiredOnCreate(), 'string', 'in:' . implode(',', FunctionalHistoryType::values())],
            'previous_value' => ['nullable'],
            'new_value' => ['nullable'],
            'effective_date' => [$this->requiredOnCreate(), 'date'],
            'document_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'exists:users,id'],
        ];
    }
}
