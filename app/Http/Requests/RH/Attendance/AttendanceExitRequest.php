<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;
use App\Support\TimeNormalizer;

/**
 * Actualização exclusiva da saída de um registo de ponto
 * (PATCH /rh/attendance/records/{id}/exit).
 *
 * Aceita `check_out` (campo canónico) ou `expected_check_out` (alias),
 * normalizando sempre para o H:i:s canónico.
 *
 * As regras de negócio (registo inexistente, sem entrada, saída já
 * registada, contexto permitido) são aplicadas no serviço, devolvendo
 * respostas 404/422 no formato do módulo.
 */
class AttendanceExitRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function prepareForValidation(): void
    {
        $checkOut = $this->input('check_out') ?? $this->input('expected_check_out');

        if ($checkOut !== null) {
            $this->merge(['check_out' => TimeNormalizer::normalize($checkOut)]);
        }
    }

    public function rules(): array
    {
        return [
            'check_out' => ['required', 'date_format:H:i:s'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'check_out.required' => 'Informe o horário de saída.',
            'check_out.date_format' => 'O horário de saída deve estar no formato H:i:s.',
        ];
    }
}