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
            'category' => ['nullable', 'integer', 'exists:positions,id'],
            'career_regime' => ['nullable', 'integer', 'exists:shifts,id'],
            'hire_date' => ['nullable', 'date'],
            'effective_date' => ['nullable', 'date'],
            'contract_type' => ['nullable', 'string', 'max:50'],
            'base_salary' => ['nullable', 'numeric', 'min:0'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_iban' => ['nullable', 'string', 'max:50'],
            'status' => ['string', 'max:30'],
            'photo_url' => ['nullable', 'file', 'max:1048576'],
            'documents' => ['nullable', 'array'],
            'documents.*.document_type' => ['nullable', 'string', 'max:100'],
            'documents.*.name' => ['nullable', 'string', 'max:255'],
            'documents.*.description' => ['nullable', 'string'],
            'documents.*.file_path' => ['required_with:documents', 'file', 'max:10485760'],
            'documents.*.expiry_date' => ['nullable', 'date'],
        ];
    }

    /**
     * Validação adicional: o funcionário deve ter, pelo menos, 18 anos
     * completos na data de admissão.
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $birth = $this->input('date_of_birth');
                $hire = $this->input('hire_date');

                if (blank($birth) || blank($hire)) {
                    return;
                }

                try {
                    $birthDate = Carbon::parse($birth);
                    $hireDate = Carbon::parse($hire);
                } catch (\Exception $e) {
                    return;
                }

                if ($birthDate->isFuture()) {
                    $validator->errors()->add('date_of_birth', 'A data de nascimento não pode ser no futuro.');

                    return;
                }

                if ($hireDate->lte($birthDate)) {
                    $validator->errors()->add('hire_date', 'A data de admissão deve ser posterior à data de nascimento.');

                    return;
                }

                if ($birthDate->diffInYears($hireDate, false) < 18) {
                    $validator->errors()->add('hire_date', 'O funcionário deve ter pelo menos 18 anos na data de admissão.');
                }
            },
        ];
    }
}
