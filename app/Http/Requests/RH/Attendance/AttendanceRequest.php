<?php

namespace App\Http\Requests\RH\Attendance;

use App\Enum\AttendanceStatus;
use App\Http\Requests\BaseFormRequest;
use App\Models\RH\Employee\Employee;
use App\Models\RH\Leave\LeaveRequest;
use App\Support\Dispensa;
use App\Support\PontoExceptions;
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
        // id do registo atual (para updates)
        $attendanceId = $this->route('id');

        return [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id', $this->notOnLeave(), $this->notExemptFromPonto(), $this->notOnFullDayDispensa()],

            'date' => [
                $this->requiredOnCreate(),
                'date',
                'before_or_equal:today', // bloqueia datas futuras
                Rule::unique('attendance', 'date')
                    ->where(fn ($query) => $query->where('employee_id', $this->input('employee_id')))
                    ->ignore($attendanceId),
            ],

            'check_in' => ['nullable', 'date_format:H:i:s'],
            'check_out' => ['nullable', 'date_format:H:i:s'],
            'status' => ['string', 'in:'.implode(',', AttendanceStatus::values())],
            'notes' => ['nullable', 'string'],
        ];
    }

    /**
     * Regra de negócio: não é permitido registar ponto para funcionários de férias.
     */
    private function notOnLeave(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $date = $this->input('date');

            if (! $date || ! $value) {
                return;
            }

            $onLeave = LeaveRequest::where('employee_id', $value)
                ->where('status', 'approved')
                ->whereDate('start_date', '<=', $date)
                ->whereDate('end_date', '>=', $date)
                ->exists();

            if ($onLeave) {
                $fail('Funcionário de férias: não é permitido registar ponto nesta data.');
            }
        };
    }

    /**
     * Regra de negócio: gabinetes com excepção ao livro de ponto do RH
     * (definidos em config/rh.php) não podem ter registos de ponto.
     */
    private function notExemptFromPonto(): \Closure
    {
        return function ($attribute, $value, $fail) {
            if (! $value) {
                return;
            }

            $employee = Employee::with('department')->find($value);

            if ($employee && PontoExceptions::isEmployeeExempt($employee)) {
                $fail('Gabinete com excepção no livro de ponto: este funcionário não assina o ponto no RH.');
            }
        };
    }

    /**
     * Regra de negócio: funcionários com dispensa aprovada (dia inteiro) na
     * data não podem ter registos de ponto.
     */
    private function notOnFullDayDispensa(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $date = $this->input('date');

            if (! $date || ! $value) {
                return;
            }

            if (Dispensa::approvedFullDayForDate((int) $value, Carbon::parse($date)->format('Y-m-d'))) {
                $fail('Funcionário com dispensa aprovada nesta data: não é permitido registar o ponto.');
            }
        };
    }

    public function messages(): array
    {
        return [
            'date.unique' => 'Já existe um registo de ponto para este funcionário nesta data.',
            'date.before_or_equal' => 'Não é permitido registar ponto com data futura.',
        ];
    }
}
