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
        $id = $this->route('functional_history_item');
        return [
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'type' => ['required', 'string', 'in:' . implode(',', FunctionalHistoryType::values())],
            'previous_value' => ['nullable'],
            'new_value' => ['nullable'],
            'effective_date' => ['required', 'date'],
            'document_reference' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'created_by' => ['nullable', 'exists:users,id'],
        ];
    }
}
