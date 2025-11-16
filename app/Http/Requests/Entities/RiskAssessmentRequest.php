<?php

namespace App\Http\Requests\Entities;

use App\Enum\FormEstablishment;
use App\Enum\StatusResidence;
use App\Http\Requests\BaseFormRequest;
use App\Models\Entities\Entities;
use App\Models\Entities\ProductRisk;
use App\Models\Indicator\IndicatorType;
use Illuminate\Validation\Rule;

class RiskAssessmentRequest extends BaseFormRequest
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
        return [
            'identification_capacity' => ['required', Rule::exists(IndicatorType::class, 'id')],
            'form_establishment' => ['required', Rule::enum(FormEstablishment::class)],
            'category' => [ 'nullable'],
            'status_residence' => ['required', Rule::enum(StatusResidence::class)],
            'profession' => ['required', Rule::exists(IndicatorType::class, 'id')],
            'pep' => ['required', 'boolean'],
            'country_residence' => ['required', Rule::exists(IndicatorType::class, 'id')],
            'nationality' => ['required', Rule::exists(IndicatorType::class, 'id')],
            'entity_id' => ['required', Rule::exists(Entities::class, 'id')],
            'channel' => ['required', Rule::exists(IndicatorType::class, 'id')],
            'product_risk' => ['nullable', 'array'],
            'product_risk.*' => ['required', Rule::exists(IndicatorType::class, 'id')],
            'beneficial_owners' => ['nullable', 'array'],
            'beneficial_owners.*.name' => ['required', 'string', 'max:255'],
         'beneficial_owners.*.percentage' => ['nullable', 'integer', 'min:0', 'max:100'],
            'beneficial_owners.*.is_legal_representative' => ['nullable', 'boolean'],
              'beneficial_owners.*.nationality' => [ 'nullable','string', 'max:255'],


            'beneficial_owners.*.pep' => ['required', 'boolean'],
        ];
    }

    public function attributes()
    {
        return [
            'identification_capacity' => 'Capacidade de Identificação',
            'form_establishment' => 'Forma de Estabelecimento',
            'category' => 'Categoria',
            'status_residence' => 'Status de Residência',
            'profession' => 'Profissão',
            'pep' => 'PEP',
            'country_residence' => 'País de Residência',
            'nationality' => 'Nacionalidade',
            'entity_id' => 'Entidade',
            'channel' => 'Canal',
            'product_risk' => 'Risco do Produto',
            'product_risk.*' => 'Risco do Produto',
        ];
    }
}
