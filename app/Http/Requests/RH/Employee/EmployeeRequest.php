<?php

namespace App\Http\Requests\RH\Employee;

use App\Http\Requests\BaseFormRequest;
use Carbon\Carbon;

class EmployeeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_number' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:employees,employee_number,{$id},id"],
            'full_name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],
            'gender' => ['nullable', 'string', 'max:20'],
            'marital_status' => ['nullable', 'string', 'max:30'],
            'nationality' => ['nullable', 'string', 'max:100'],
            'document_type' => ['nullable', 'string', 'max:50'],
            'document_number' => ['nullable', 'string', 'max:50'],
            'nif' => ['nullable', 'string', 'max:30', "unique:employees,nif,{$id},id"],
            'personal_email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'position_id' => ['nullable', 'integer', 'exists:positions,id'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'hire_date' => ['nullable', 'date'],
            'effective_date' => ['nullable', 'date'],
            'contract_type' => ['nullable', 'string', 'max:50'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:50'],
            'status' => ['string', 'max:30'],
            'photo_url' => [
                'nullable',
                function ($attribute, $value, \Closure $fail) {
                    if (is_string($value)) {
                        // Reenvio do valor actual (caminho em storage) é aceite;
                        // lixo como /tmp/phpXXX ou URLs são rejeitados
                        if (!preg_match('#^storage/.+#', trim($value))) {
                            $fail('A fotografia deve ser um ficheiro enviado (multipart) ou um caminho válido em storage.');
                        }

                        return;
                    }

                    if (!$value instanceof \Illuminate\Http\UploadedFile) {
                        $fail('A fotografia deve ser um ficheiro enviado (multipart).');

                        return;
                    }

                    if ($value->getError() !== UPLOAD_ERR_OK) {
                        $fail('O upload da fotografia falhou.');

                        return;
                    }

                    if ($value->getSize() > 1048576) {
                        $fail('A fotografia não pode exceder 1MB.');
                    }
                },
            ],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['nullable', 'string', 'max:100'],
            'documents.*.description' => ['nullable', 'string'],
            'documents.*.file_path' => ['required_with:documents', 'file', 'max:10485760'],
            'documents.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Validação adicional: o funcionário deve ter, pelo menos, 18 anos
     * completos na data de admissão e na data de efetivação.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $birth = $this->input('date_of_birth');

                if (blank($birth)) {
                    return;
                }

                try {
                    $birthDate = Carbon::parse($birth);
                } catch (\Exception $e) {
                    return;
                }

                if ($birthDate->isFuture()) {
                    $validator->errors()->add('date_of_birth', 'A data de nascimento não pode ser no futuro.');

                    return;
                }

                $hire = $this->input('hire_date');
                if (filled($hire)) {
                    $this->validateAgeAt($validator, $birthDate, $hire, 'hire_date', 'admissão');
                }

                $effective = $this->input('effective_date');
                if (filled($effective)) {
                    $this->validateAgeAt($validator, $birthDate, $effective, 'effective_date', 'efetivação');
                }
            },
        ];
    }

    private function validateAgeAt($validator, Carbon $birthDate, string $date, string $field, string $label): void
    {
        try {
            $parsed = Carbon::parse($date);
        } catch (\Exception $e) {
            return;
        }

        if ($parsed->lte($birthDate)) {
            $validator->errors()->add($field, "A data de {$label} deve ser posterior à data de nascimento.");

            return;
        }

        $age = (int) $birthDate->diffInYears($parsed);

        if ($age < 18) {
            $validator->errors()->add(
                $field,
                "O funcionário deve ter, pelo menos, 18 anos completos na data de {$label} ({$parsed->format('d/m/Y')}). Idade nessa data: {$age} anos."
            );
        }
    }
}
