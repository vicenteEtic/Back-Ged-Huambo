<?php

namespace App\Http\Requests\RH\Attendance;

use App\Http\Requests\BaseFormRequest;
use App\Support\Dispensa;

class AttendanceRequestTypeRequest extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');

        return [
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:attendance_request_types,code,{$id},id"],
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'legal_ref' => ['nullable', 'string', 'max:255'],
            'max_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
        ];
    }

    public function messages(): array
    {
        $labels = Dispensa::documentLabels();

        return [
            'code.unique' => 'Já existe um tipo de solicitação com este código.',
            'required_documents.*.string' => 'Cada documento obrigatório deve ser um código válido.',
            'required_documents.*.max' => 'Código de documento obrigatório demasiado longo.',
            'required_documents.*' => 'Os documentos obrigatórios devem ser códigos da lista: '.implode(', ', array_keys($labels)).'.',
        ];
    }
}
