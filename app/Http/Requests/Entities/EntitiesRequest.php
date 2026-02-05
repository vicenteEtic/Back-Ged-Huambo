<?php

namespace App\Http\Requests\Entities;

use App\Enum\TypeEntity;
use App\Http\Requests\BaseFormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class EntitiesRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $id = $this->route('entity') ? $this->route('entity') : null;
        return [
            'social_denomination' => ['nullable', 'string','max:255'],
            'customer_number' => ['nullable', 'string', 'max:255', 'unique:entities,customer_number,' . $id],
            'policy_number' => ['nullable', 'string', 'max:255', 'unique:entities,policy_number,' . $id],
            'entity_type' => ['required', 'integer', Rule::enum(TypeEntity::class)],
            'nif' =>  ['required', 'string', 'unique:entities,nif,' . $id],
        ];
    }

    public function attributes(): array
    {
        return [
            'social_denomination' => 'Denominação Social',
            'customer_number' => 'Número do Cliente',
            'policy_number' => 'Número da Apólice',
            'entity_type' => 'Tipo de Entidade',
            'nif' => 'Nif',
        ];
    }
}
