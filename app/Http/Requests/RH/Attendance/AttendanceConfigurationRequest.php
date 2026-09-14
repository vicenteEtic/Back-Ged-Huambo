<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;

/**
 * Actualização da configuração do livro de ponto
 * (PUT /rh/attendance/configuration).
 *
 * `attendance_book_departments` pode ser vazio — nesse caso a configuração
 * é limpa e o backend volta a usar a regra legada (config/rh.php → ponto).
 */
class AttendanceConfigurationRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'attendance_book_departments' => ['nullable', 'array'],
            'attendance_book_departments.*' => ['integer', 'exists:departments,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'attendance_book_departments.*.exists' => 'Há um departamento inválido na configuração.',
        ];
    }
}