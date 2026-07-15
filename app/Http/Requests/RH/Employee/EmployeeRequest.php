<?php

namespace App\Http\Requests\RH\Employee;

use App\Http\Requests\BaseFormRequest;

class EmployeeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('employee');
        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'employee_number' => ['required', 'string', 'max:50', "unique:employees,employee_number,{$id},id"],
            'full_name' => ['required', 'string', 'max:255'],
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
            'hire_date' => ['nullable', 'date'],
            'effective_date' => ['nullable', 'date'],
            'contract_type' => ['nullable', 'string', 'max:50'],
            'base_salary' => ['numeric', 'min:0'],
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
}
