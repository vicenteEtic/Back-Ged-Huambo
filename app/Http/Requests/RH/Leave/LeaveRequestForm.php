<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;
use App\Models\RH\Leave\LeavePlan;

class LeaveRequestForm extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $rules = [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'leave_type_id' => [$this->requiredOnCreate(), 'integer', 'exists:leave_types,id'],
            'leave_plan_id' => ['nullable', 'integer', 'exists:leave_plans,id'],
            'start_date' => [$this->requiredOnCreate(), 'date'],
            'end_date' => ['sometimes', 'date', 'after_or_equal:start_date'],
            'days' => ['sometimes', 'integer', 'min:1', 'max:366'],
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

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->filled('leave_plan_id')) {
                return;
            }

            $plan = LeavePlan::find($this->input('leave_plan_id'));
            $leaveTypeId = $this->input('leave_type_id');
            $employeeId = $this->input('employee_id');

            if ($id = $this->route('id')) {
                $current = \App\Models\RH\Leave\LeaveRequest::find($id);
                $leaveTypeId ??= $current?->leave_type_id;
                $employeeId ??= $current?->employee_id;
            }

            if ($plan && (($leaveTypeId !== null && (int) $plan->leave_type_id !== (int) $leaveTypeId)
                || ($employeeId !== null && (int) $plan->employee_id !== (int) $employeeId))) {
                $validator->errors()->add(
                    'leave_plan_id',
                    'O plano de férias não é compatível com o funcionário e o tipo de licença.'
                );
            }
        });
    }
}
