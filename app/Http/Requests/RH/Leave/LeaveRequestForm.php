<?php

namespace App\Http\Requests\RH\Leave;

use App\Http\Requests\BaseFormRequest;
use App\Models\RH\Leave\LeavePlan;
use App\Models\RH\Leave\LeaveRequest;

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
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
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
            $id = $this->route('id');

            if ($id) {
                $current = LeaveRequest::find($id);
            } else {
                $current = null;
            }

            // Na criação: exige end_date ou days (a menos que o frontend indique tempo indeterminado)
            // Na edição: permite actualização parcial de end_date
            if (! $id && ! $this->filled('end_date') && ! $this->filled('days')) {
                $leaveTypeId = $this->input('leave_type_id');
                if ($leaveTypeId) {
                    $leaveType = \App\Models\RH\Leave\LeaveType::find($leaveTypeId);
                    $allowsIndefinite = $leaveType && $this->allowsIndefiniteLeave($leaveType);
                    if (! $allowsIndefinite) {
                        $validator->errors()->add(
                            'end_date',
                            'A data de término é obrigatória para este tipo de licença. Envie "end_date" ou "days".'
                        );
                    }
                }
            }

            // Validação de leave_plan_id
            if (! $this->filled('leave_plan_id')) {
                return;
            }

            $plan = LeavePlan::find($this->input('leave_plan_id'));
            $leaveTypeId = $this->input('leave_type_id');
            $employeeId = $this->input('employee_id');

            if ($id) {
                $currentPlan = $current;
                $leaveTypeId ??= $currentPlan?->leave_type_id;
                $employeeId ??= $currentPlan?->employee_id;
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

    /**
     * Verifica se o tipo de licença permite tempo indeterminado.
     * Regra: decidida pelo próprio tipo de licença (coluna
     * `allows_indefinite_duration` em leave_types) — não escolhível
     * livremente para qualquer licença.
     */
    private function allowsIndefiniteLeave(\App\Models\RH\Leave\LeaveType $leaveType): bool
    {
        return (bool) $leaveType->allows_indefinite_duration;
    }
}
