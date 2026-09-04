<?php

namespace App\Http\Requests\RH\Attendance;

use App\Enum\AttendanceStatus;
use App\Http\Requests\BaseFormRequest;
use App\Support\TimeNormalizer;
use Carbon\Carbon;
use Illuminate\Validation\Rule;

class AttendanceRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->has('check_in')) {
            $this->merge(['check_in' => TimeNormalizer::normalize($this->input('check_in'))]);
        }
        if ($this->has('check_out')) {
            $this->merge(['check_out' => TimeNormalizer::normalize($this->input('check_out'))]);
        }
        if ($this->has('date')) {
            $this->merge(['date' => Carbon::parse($this->input('date'))->format('Y-m-d')]);
        }
    }

    public function rules(): array
    {
        // id do registo atual (para updates) — ajusta o nome do parâmetro de rota se for diferente
        $attendanceId = $this->route('attendance')?->id ?? $this->route('attendance');

        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],

            'date' => [
                $this->requiredOnCreate(),
                'date',
                'before_or_equal:today', // bloqueia datas futuras
                Rule::unique('attendances', 'date')
                    ->where(fn ($query) => $query->where('employee_id', $this->input('employee_id')))
                    ->ignore($attendanceId),
            ],

            'shift_id' => ['nullable', 'integer', 'exists:shifts,id'],
            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['string', 'in:' . implode(',', AttendanceStatus::values())],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'Já existe um registo de ponto para este funcionário nesta data.',
            'date.before_or_equal' => 'Não é permitido registar ponto com data futura.',
        ];
    }
}