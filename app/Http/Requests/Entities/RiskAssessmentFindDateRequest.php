<?php

namespace App\Http\Requests\Entities;

use App\Enum\FormEstablishment;
use App\Enum\StatusResidence;
use App\Http\Requests\BaseFormRequest;
use App\Models\Entities\Entities;
use App\Models\Entities\ProductRisk;
use App\Models\Indicator\IndicatorType;
use Illuminate\Validation\Rule;

class RiskAssessmentFindDateRequest extends BaseFormRequest
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
           
            'startDate' => ['nullable'],
            'endDate' => ['nullable'],     
        ];
    }

    public function attributes()
    {
        return [
            'startDate' => 'startDate',
            'endDate' => 'endDate',
            
        ];
    }
}
