<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;
use App\Support\TimeNormalizer;
use Carbon\Carbon;

/**
 * Marcação de saída em lote (PATCH /rh/attendance/records/bulk-exit).
 *
 * Aceita `check_out` (campo canónico) ou `expected_check_out` (alias) em
 * cada registo, normalizando sempre para H:i:s. A validação de negócio
 * por funcionário é feita individualmente no serviço.
 */
class AttendanceBulkExitRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        if ($this->has('date') && $this->input('date')) {
            $this->merge(['date' => Carbon::parse($this->input('date'))->format('Y-m-d')]);
        }

        $records = collect($this->input('records', []))
            ->map(function ($row) {
                $checkOut = $row['check_out'] ?? $row['expected_check_out'] ?? null;

                $row['employee_id'] = isset($row['employee_id']) ? (int) $row['employee_id'] : null;

                if ($checkOut !== null) {
                    $row['check_out'] = TimeNormalizer::normalize($checkOut);
                }

                return $row;
            })
            ->values()
            ->all();

        $this->merge(['records' => $records]);
    }

    public function rules(): array
    {
        return [
            'date' => ['required', 'date'],
            'records' => ['required', 'array', 'min:1', 'max:200'],
            'records.*.employee_id' => ['required', 'integer', 'distinct', 'exists:employees,id'],
            'records.*.check_out' => ['required', 'date_format:H:i:s'],
            'records.*.notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required' => 'Informe a data do registo.',
            'records.required' => 'Informe pelo menos um funcionário.',
            'records.min' => 'Informe pelo menos um funcionário.',
            'records.*.employee_id.required' => 'Há um funcionário sem identificação.',
            'records.*.employee_id.exists' => 'Há um funcionário inválido no pedido.',
            'records.*.employee_id.distinct' => 'O mesmo funcionário foi enviado mais de uma vez.',
            'records.*.check_out.required' => 'Informe o horário de saída de cada funcionário.',
            'records.*.check_out.date_format' => 'O horário de saída deve estar no formato H:i:s.',
        ];
    }
}