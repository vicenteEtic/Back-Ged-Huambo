<?php

namespace App\Http\Requests\RH\Declaration;

use App\Http\Requests\BaseFormRequest;

class DeclarationRequestForm extends BaseFormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $id = $this->route('id');
        $rules = [
            'employee_id' => [$this->requiredOnCreate(), 'integer', 'exists:employees,id'],
            'declaration_type_id' => [$this->requiredOnCreate(), 'integer', 'exists:declaration_types,id'],
            'institution_name' => ['nullable', 'string', 'max:255'],
            'institution_type' => ['nullable', 'string', 'max:100'],
            'purpose' => ['nullable', 'string'],
            'additional_info' => ['nullable', 'string'],
        ];

        $rules += $this->declarationFieldRules();

        if ($id) {
            $rules['status'] = ['string', 'max:30'];
            $rules['issued_number'] = ['nullable', 'string', 'max:50'];
            $rules['notes'] = ['nullable', 'string'];
        }

        return $rules;
    }

    protected function declarationFieldRules(): array
    {
        $text = ['nullable', 'string', 'max:255'];

        return [
            'nome_completo' => $text,
            'sexo' => ['nullable', 'string', 'in:masculino,feminino'],
            'data_emissao' => ['nullable', 'date'],
            'numero_declaracao' => ['nullable', 'string', 'max:50'],
            'assinante_nome' => $text,
            'assinante_cargo' => $text,
            'categoria_funcao' => $text,
            'local_servico' => $text,
            'vinculo' => $text,
            'banco' => ['nullable', 'string', 'max:100'],
            'tipo_salario' => ['nullable', 'string', 'in:base,liquido,base_e_liquido'],
            'salario_numero' => ['nullable', 'numeric', 'min:0'],
            'salario_extenso' => ['nullable', 'string', 'max:500'],
            'salario_numero_liquido' => ['nullable', 'numeric', 'min:0'],
            'salario_extenso_liquido' => ['nullable', 'string', 'max:500'],
            'tratamento' => ['nullable', 'string', 'max:100'],
            'cargo' => $text,
            'tempo_servico' => $text,
            'data_admissao' => ['nullable', 'string', 'max:50'],
            'data_admissao_completa' => ['nullable', 'date'],
            'numero_conta' => ['nullable', 'string', 'max:50'],
            'conta_consignacao' => ['nullable', 'string', 'max:50'],
            'numero_bi' => ['nullable', 'string', 'max:50'],
            'numero_agente' => ['nullable', 'string', 'max:50'],
            'telefone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'morada' => ['nullable', 'string'],
            'balcao_domicilio' => $text,
            'finalidade' => $text,
            'entidade_empregadora' => $text,
            'entidade_pagadora' => $text,
            'dia_pagamento' => $text,
            'embaixada' => $text,
            'cidade_embaixada' => $text,
            'local_residencia' => $text,
            'tipo_correccao' => ['nullable', 'string', 'in:correccao,acrescimo'],
            'departamento_emissor' => $text,
        ];
    }
}
