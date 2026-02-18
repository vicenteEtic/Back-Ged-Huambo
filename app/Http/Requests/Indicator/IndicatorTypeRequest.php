<?php

namespace App\Http\Requests\Indicator;

use App\Http\Requests\BaseFormRequest;

class IndicatorTypeRequest extends BaseFormRequest
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
            'description' => ['required', 'string', 'max:255'],
            'score' => ['required', 'integer'],
            'risk' => ['required', 'string', 'max:255'],
            'has_beneficial'=> ['nullable', 'boolean'],
        ];
    }

    public function attributes()
    {
        return [
            'description' => 'Descrição',
            'score' => 'Pontuação',
            'risk' => 'Risco',
        ];
    }
}
