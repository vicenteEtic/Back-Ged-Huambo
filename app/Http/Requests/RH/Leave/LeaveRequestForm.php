<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;

class LeaveRequestForm extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('leave_request');
        $rules = [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'leave_type_id' => [$this->requiredOnCreate(), 'integer', 'exists:leave_types,id'],
            'leave_plan_id' => ['nullable', 'integer', 'exists:leave_plans,id'],
            'start_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => [$this->requiredOnCreate(), 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string'],
        ];

        if ($id) {
            $rules['status'] = ['string', 'max:30'];
            $rules['approved_by'] = ['nullable', 'exists:users,id'];
            $rules['approved_at'] = ['nullable', 'date'];
            $rules['rejection_reason'] = ['nullable', 'string'];
        }

        return $rules;
    }
}
