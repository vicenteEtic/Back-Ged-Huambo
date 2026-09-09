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
        $allowsExtension = $this->boolean('allows_extension', false);

        $rules = [
            'code' => [$this->requiredOnCreate(), 'string', 'max:50', "unique:attendance_request_types,code,{$id},id"],
            'name' => [$this->requiredOnCreate(), 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'legal_ref' => ['nullable', 'string', 'max:255'],
            'max_days' => ['nullable', 'integer', 'min:1', 'max:3650'],
            'required_documents' => ['nullable', 'array'],
            'required_documents.*' => ['string', 'max:50'],
            'is_active' => ['boolean'],
            'sort_order' => ['nullable', 'integer'],
            'allows_extension' => ['boolean'],
            'extension_days' => $allowsExtension
                ? ['required', 'integer', 'min:1', 'max:3650']
                : ['nullable', 'integer', 'min:1', 'max:3650'],
            'max_extensions' => $allowsExtension
                ? ['required', 'integer', 'min:1', 'max:100']
                : ['nullable', 'integer', 'min:1', 'max:100'],
        ];

        return $rules;
    }

    public function messages(): array
    {
        $labels = Dispensa::documentLabels();

        return [
            'code.unique' => 'Já existe um tipo de solicitação com este código.',
            'required_documents.*.string' => 'Cada documento obrigatório deve ser um código válido.',
            'required_documents.*.max' => 'Código de documento obrigatório demasiado longo.',
            'required_documents.*' => 'Os documentos obrigatórios devem ser códigos da lista: '.implode(', ', array_keys($labels)).'.',
            'extension_days.required' => 'A quantidade de dias por prorrogação é obrigatória quando a prorrogação está activa.',
            'extension_days.min' => 'A quantidade de dias por prorrogação deve ser no mínimo 1.',
            'extension_days.integer' => 'A quantidade de dias por prorrogação deve ser um número inteiro.',
            'max_extensions.required' => 'O número máximo de prorrogações é obrigatório quando a prorrogação está activa.',
            'max_extensions.min' => 'O número máximo de prorrogações deve ser no mínimo 1.',
            'max_extensions.integer' => 'O número máximo de prorrogações deve ser um número inteiro.',
        ];
    }
}
