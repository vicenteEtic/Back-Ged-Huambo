<?php

namespace App\Http\Requests\RH\EmployeeDocument;

use App\Http\Requests\BaseFormRequest;
use App\Models\RH\EmployeeDocument\DocumentType;

class EmployeeDocumentRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        return [
            'document_type_id' => ['nullable', 'integer', 'exists:document_types,id'],
            'document_type' => ['nullable', 'string', 'max:100'],
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'description' => ['nullable', 'string'],
            'file_path' => [$this->requiredOnCreate(), 'array'],
            'file_path.*' => ['file', 'max:1048576'],
            'expiry_date' => ['nullable', 'date'],
            'is_lifetime' => ['boolean'],
            'issue_date' => ['nullable', 'date'],
            'place_of_issue' => ['nullable', 'string', 'max:255'],
            'is_verified' => ['boolean'],
        ];
    }

    /**
     * Campos obrigatórios conforme as características do tipo de documento
     * (ex.: BI tem data de emissão e data de expiração).
     */
    public function after(): array
    {
        return [
            function ($validator) {
                $typeId = $this->input('document_type_id');
                $isLifetime = filter_var($this->input('is_lifetime', false), FILTER_VALIDATE_BOOLEAN);

                if ($isLifetime) {
                    return;
                }

                if (blank($typeId)) {
                    return;
                }

                $type = DocumentType::find($typeId);

                if (! $type) {
                    return;
                }

                if ($type->has_expiry_date && blank($this->input('expiry_date'))) {
                    $validator->errors()->add('expiry_date', 'O '.$type->name.' requer a data de expiração.');
                }

                if ($type->has_issue_date && blank($this->input('issue_date'))) {
                    $validator->errors()->add('issue_date', 'O '.$type->name.' requer a data de emissão.');
                }

                if ($type->has_place_of_issue && blank($this->input('place_of_issue'))) {
                    $validator->errors()->add('place_of_issue', 'O '.$type->name.' requer o local de emissão.');
                }
            },
        ];
    }
}
